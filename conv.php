<?php
include_once "conf.php";

$target_dir = "uploads/";
$fileid = uniqid();
// $pfx_file = $target_dir. $fileid.".pfx";;
$crt_file = $target_dir. $fileid.".crt";
$key_file = $target_dir. $fileid.".key";

$cert_password = $_POST["pass"];
$res = [];
$tmp_file = $_FILES["arq"]['tmp_name'];
$tmp_contents = file_get_contents($tmp_file);
if ($tmp_contents)
{
  $SSL_ok = openssl_pkcs12_read(
      $tmp_contents,
      $res, $cert_password
  );
  if ( !$SSL_ok)
  {
    echo "Error reading pfx file";
    exit(4);
  }
} else {
  echo "Could not open: {$tmp_file}";
  exit(3);

}

try {
  $stmt = $pdo->prepare(<<<EOS
INSERT INTO cert (
  pfx, cert, pkey, pass, subject, issuer, not_before, not_after, raw
)
VALUES (
  :pfx, :cert, :pkey, :pass, :subject, :issuer, :not_before, :not_after, :raw
)
ON CONFLICT (subject) DO
UPDATE SET
  pfx = excluded.pfx,
  cert = excluded.cert,
  pkey = excluded.pkey,
  pass = excluded.pass,
  subject = excluded.subject,
  issuer = excluded.issuer,
  not_before = excluded.not_before,
  not_after = excluded.not_after,
  raw = excluded.raw
EOS
);

} catch (PDOException $e) {
    print "Error: " . $e->getMessage();
    exit(1);
}


$cert = openssl_x509_parse($res['cert']);
if ($cert) {
  $subject = "";
  $issuer= "";

  foreach ($cert['subject'] as $key => $val)
  {
    $subject .= $key . "=". $val. ", ";
  }

  foreach ($cert['issuer'] as $key => $val)
  {
    $issuer .= $key . "=". $val. ", ";
  }

  $subject = rtrim ($subject, ", ");
  $issuer  = rtrim ($issuer, ", ");

  print "subject: " . $subject. PHP_EOL;
  print "issuer: " . $issuer. PHP_EOL;

  $pfx_file = $target_dir. str_replace(" ", "-", $subject).".pfx";;

  try {
    $stmt->execute(
      [
        ':pfx'        => $pfx_file,
        ':cert'       => $res['cert'],
        ':pkey'       => $res['pkey'],
        ':pass'       => $cert_password,
        ':subject'    => $subject,
        ':issuer'     => ($subject == $issuer) ? null : $issuer,
        ':not_before' => date("Y/m/d", $cert['validFrom_time_t']),
        ':not_after'  => date("Y/m/d", $cert['validTo_time_t']),
        ':raw'        => json_encode($cert),
    ]);
  } catch (PDOException $e) {
    print "On execute". PHP_EOL;
    print "Error: " . $e->getMessage(). PHP_EOL;
    exit(2);
  }
} else {
  print "Não consegui extrair x509 do arquivo {$_FILES['arq']['tmp_name'][$i]}". PHP_EOL;
  print "Conteudo:". PHP_EOL;
  // print $content;
}


if (move_uploaded_file($_FILES["arq"]["tmp_name"], $pfx_file)) {
    echo "File uploaded";
} else {
    echo "File can't be uploaded";
}

// file_put_contents($crt_file, $res['cert']);

// file_put_contents($key_file, $res['pkey']);

// this is the PEM FILE
//$cert = $res['cert'].implode('', $res['extracerts']);
//file_put_contents('KEY.pem', $cert);

// print_r($dados);

// echo $dados[""]. PHP_EOL;
echo "<pre>";

echo "Subject :\t" . $cert["subject"]["CN"] . PHP_EOL;
echo "Valid to:\t" . date("Y/m/d", $cert["validTo_time_t"]) . PHP_EOL;
echo "Serial  :\t" . $cert["serialNumberHex"] . PHP_EOL;
echo "Issuer: " . PHP_EOL;
echo "\tC:\t" . $cert["issuer"]["C"] . PHP_EOL;
echo "\tL:\t" . $cert["issuer"]["L"] . PHP_EOL;
echo "\tO:\t" . $cert["issuer"]["O"] . PHP_EOL;
echo "\tCN:\t" . $cert["issuer"]["CN"] . PHP_EOL;
echo "Purposes:" . PHP_EOL;

foreach ($cert["purposes"] as $purpose) {
    echo "\t". $purpose[2]. PHP_EOL;
}
print_r($cert);
echo "</pre>";
