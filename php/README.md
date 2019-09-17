# aemoph

ควรใช้ php version 5.6+

การ config
```
/*==========================================================================
config connect to `is` database
*/
$isDB = [
    'host' => 'localhost',      // ที่เก็บ isdb
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
                    // และเวลาต้องสัมพันธ์กับที่ตั้ง crontab ด้วย

/*========================================================================*/
```

## Running
```
Linux
$ sudo crontab -e

# add
*/5 * * * * php /<directory>/send2moph.php

Microsoft windows ให้อ่านได้ที่
[https://medium.com/](https://medium.com/arcadia-software-development/%E0%B8%A7%E0%B8%B4%E0%B8%98%E0%B8%B5%E0%B8%81%E0%B8%B2%E0%B8%A3%E0%B8%95%E0%B8%B1%E0%B9%89%E0%B8%87%E0%B8%84%E0%B9%88%E0%B8%B2-task-scheduler-%E0%B9%83%E0%B8%99%E0%B8%A3%E0%B8%B0%E0%B8%9A%E0%B8%9A%E0%B8%9B%E0%B8%8F%E0%B8%B4%E0%B8%9A%E0%B8%B1%E0%B8%95%E0%B8%B4%E0%B8%81%E0%B8%B2%E0%B8%A3-windows-3f8aa7173774)
```

