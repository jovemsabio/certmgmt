#!/usr/bin/php
<?php
$pdo = new PDO('sqlite:./db2.sqlt');

$pdo->exec("DROP TABLE cert");
$pdo->exec(<<<EOT
  CREATE TABLE cert (
    id integer primary key autoincrement,
    pfx text,
    cert text,
    pkey text,
    pass varchar(100),
    subject text unique,
    issuer text,
    not_before date,
    not_after date,
    raw JSON
  );
EOT
);
// INSERTION TEST OF A JSON OBJECT INTO A TABLE FIELD WITH THE NAME "RAW"
// PREPARES A QUERY
// $stmt = $pdo->prepare("insert into cert (raw) values (:data)");
//
// RUNS THE QUERY
// $data = ['teste'=>20, 'nome'=>'Texto'];
// try {
//   $stmt->execute(
//     [
//       ':data'        => json_encode($data),
//   ]);
// } catch (PDOException $e) {
//   print "On execute". PHP_EOL;
//   print "Error: " . $e->getMessage(). PHP_EOL;
//   exit(2);
// }

