<?php
date_default_timezone_set("Asia/Bangkok");
error_reporting(E_ERROR | E_PARSE);
ini_set('memory_limit', '512M');

/*==========================================================================
config connect to `is` database
*/
$isDB = [
    'host' => 'localhost',
    'username' => 'isuser',
    'password' => 'password',
    'port' => '3306',
    'dbname' => 'isdb',
    'charset' => 'utf8',
    'socket' => "",
];

// config ค่าสำหรับการส่งข้อมูลเข้ากระทรวง
$mophUser = [
    'url' => 'http://ict-pher.moph.go.th:8080/v2/',
    'username' => "user",     // ระบุ username ที่ขอไว้กับระทรวง
    'password' => "password",       // ระบุ password ที่ให้ไว้กับกระทรวง
    'mophTableName' => 'is'         // ชื่อตารางข้อมูลที่บันทึกในกระทรวง
];

/*========================================================================*/

// กำหนดช่วงเวลาที่ต้องการ get ข้อมูล
$backwardTime = 10; //minute, ระยะเวลาที่อ่านข้อมูลย้อนหลัง
if (date("H:i:s") == "01:00:00" || date("H:i:s") == "06:00:00") {
    $Date1 = date("Y-m-d H:i:s", mktime(date("H"), date("i"), 0, date("m"), date("d") - 1, date("Y")));
    $dateColumn = 'adate';
} else {
    $Date1 = date("Y-m-d H:i:s", mktime(date("H"), date("i") - $backwardTime - 1, 0, date("m"), date("d"), date("Y")));
    $dateColumn = 'lastupdate';
}
$Date2 = date("Y-m-d H:i:s");

/*========================================================================*/
// กรณีที่ต้องการกำหนดวันที่เอง เพื่อส่งย้อนหลัง (ควรทำครั้งเดียว) ให้กำหนดวันที่เอง เช่น
// $Date1 = "2018-10-01 00:00:00";
// $Date2 = "2018-10-31 23:59:59";
// $dateColumn = 'adate';
/*========================================================================*/

echo "Start Process: ", date("Y-m-d H:i:s"), "\n";
echo "crontab name: send `is` data to moph \n";
echo "from: ", $Date1, " to ", $Date2, "\n";

// สร้าง token เพื่อใช้ในการส่งข้อมูล
$token = getToken($mophUser);
if (!$token || $token == '') {
    echo "Can't get tokenKey\n";
    return;
}

// connect ไปยัง Mysql
$isTable = $isDB["dbname"];
$isDBconnect = new mysqli($isDB["host"], $isDB["username"], $isDB["password"], $isTable, $isDB["port"])
    or die('Could not connect to the database server' . mysqli_connect_error());
$isDBconnect->query("SET NAMES " . $isDB["charset"]);

// อ่านข้อมูล is
$sql = "select * from $isTable.`is` where $dateColumn between '$Date1' and '$Date2' order by $dateColumn ";
$result = $isDBconnect->query($sql);
$reccount = $result->num_rows;
echo 'founded: ',$reccount, ' rec.';
if ($reccount <= 0) {
    $result->close();
    return;
}

// loop อ่านข้อมูลเพื่อส่งที่ละ record
$rw = [];
$recno = 0;
while ($row = $result->fetch_array()) {
    unset($row["ref"]);
    unset($row["lastupdate"]);
    foreach ($row as $column => $value) {
        if ($column == '0' || $column + 0 > 0) {
            unset($row[$column]);
        }
    }
    $rw[0] = $row;

    // ส่งข้อมูลไปยังกระทรวง
    $ret = send_moph($rw, $token, $mophUser);
    echo ++$recno.'/'.$reccount, '>',$row["hn"], ' ', $row["id"], "\n";
}

// ปิด connection mysql
$isDBconnect->close();

// สั่ง expire token เพื่อป้องกันการแอบใช้งาน
expireToken($token, $mophUser);

echo "\nEnd Process: ", date("Y-m-d H:i:s"), "\n";
return;

// request send function =========================================
function send_moph($data, $token, $mophUser) {
    $request = [];
    $request["tokenKey"] = $token;
    $request["tableName"] = $mophUser['mophTableName'];
    $request["content"] = json_encode($data);

    $Req = curl_init();
    curl_setopt($Req, CURLOPT_URL, $mophUser['url'].'save');
    curl_setopt($Req, CURLOPT_POST, 1);
    curl_setopt($Req, CURLOPT_POSTFIELDS, http_build_query($request));
    curl_setopt($Req, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($Req, CURLOPT_SSL_VERIFYPEER, 0);
    $server_output = curl_exec($Req);
    curl_close($Req);

    $ret = (array) json_decode($server_output);
    return $ret;
}

// request create token function =========================================
function getToken($mophUser) {
    $request = [];
    $request["username"] = $mophUser['username'];
    $request["password"] = $mophUser['password'];

    $Req = curl_init();
    curl_setopt($Req, CURLOPT_URL, $mophUser['url'].'create-token/');
    curl_setopt($Req, CURLOPT_POST, 1);
    curl_setopt($Req, CURLOPT_POSTFIELDS, http_build_query($request));
    curl_setopt($Req, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($Req, CURLOPT_SSL_VERIFYPEER, 0);
    $server_output = curl_exec($Req);
    curl_close($Req);

    $ret = (array) json_decode($server_output);
    return $ret["token"];
}

// request expire token function =========================================
function expireToken($token, $mophUser) {
    $Url = $mophUser['url'] . 'expire-token/' . $token;
    $request = [];
    $Req = curl_init();
    curl_setopt($Req, CURLOPT_URL, $Url);
    curl_setopt($Req, CURLOPT_POST, 1);
    curl_setopt($Req, CURLOPT_POSTFIELDS, http_build_query($request));
    curl_setopt($Req, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($Req, CURLOPT_SSL_VERIFYPEER, 0);
    $server_output = curl_exec($Req);
    curl_close($Req);

    return $server_output;
}
