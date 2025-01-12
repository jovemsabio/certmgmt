<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

try {
    $pdo = new PDO('sqlite:./../../db2.sqlt');
} catch (PDOException $e) {
    print "Connection error: " . $e->getMessage();
    exit(55);
}
$result = [];

// Parameters
$subject = $_GET['subject']??Null;
$id = $_GET['id']??Null;
$plain_full = $_GET['plain_full']??False;
$plain_cert = $_GET['plain_cert']??False;

$criteria = [];
if ($id !== Null) {
    $criteria["where"] = "cert.id = :id";
    $criteria['bind'] = [":id"=>$id];
} else if ($subject !== Null) {
    $criteria["where"] = "cert.subject= :subject";
    $criteria['bind'] = [":subject"=>$subject];
} else {
    print "either ?id or ?subject is required";
    exit(5);
}

try {
    $stmt = $pdo->prepare(
//     <<<QUERY
//         SELECT
//             cert.id AS id,
//             cert.subject AS subject,
//             cert.cert AS cert,
//             cert.pkey AS pkey,
//             a.cert AS cert_a,
//             b.cert AS cert_b
//     FROM cert
//     INNER JOIN cert_chain AS a ON cert.issuer = a.cn
//     INNER JOIN cert_chain b ON a.issuer = b.cn
//     WHERE {$criteria['where']} 
// QUERY //End of Query
    <<<EOT
    WITH RECURSIVE
      init (id, cert,subject,issuer, pkey) as (
        SELECT id, cert, subject, issuer, pkey from cert where {$criteria['where']}
        UNION ALL
        SELECT
          cert.id,
          cert.cert,
          cert.subject,
          cert.issuer,
          NULL
        FROM cert, init
        WHERE cert.subject = init.issuer
      
    )
    select id, subject, cert, issuer, pkey from init;
EOT //End of Query
);
    $stmt->execute($criteria['bind']);
} catch (PDOException $e) {
    header("Content-type: text/plain");
    print "Error: " . $e->getMessage();
    exit(1);
}
try {
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (count($result) == 0) {

    }
} catch (PDOException $e) {
    print "Error: " . $e->getMessage();
    exit(1);
}

// $result['plain_full'] = $plain_full;
if ($plain_full != False) {
    header("Content-type: text/plain");
    foreach ($result as $line) {
      print trim($line['cert']). PHP_EOL;
    }
    // $result['plain_full'] = $plain_full;
} else if ($plain_cert != False){
    header("Content-type: text/plain");
    print trim($result[0]['cert'], '\n'). PHP_EOL;
} else {
    header("Content-type: application/json");
    print json_encode($result, JSON_PRETTY_PRINT);
}
// print_r($result);
// print json_encode($result, JSON_FORCE_OBJECT);
