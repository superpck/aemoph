# aemoph

config
```
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

$backwardTime = 10; //minute, ระยะเวลาที่อ่านข้อมูลย้อนหลัง

/*========================================================================*/
```

## Running

```
```

