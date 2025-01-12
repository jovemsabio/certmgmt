<?php
require_once "conf.php";
require_once "lib/functions.php";

try {
  $stmt = $pdo->prepare(<<<EOS
INSERT INTO cert (
  pfx, cert, pkey, pass, subject, issuer, not_before, not_after
)
VALUES (
  :pfx, :cert, :pkey, :pass, :subject, :issuer, :not_before, :not_after
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
  not_after = excluded.not_after
EOS
);

} catch (PDOException $e) {
    print "Error: " . $e->getMessage();
    exit(1);
}

echo "<pre>";
// print_r($_FILES);
// print count($_FILES['arq']). PHP_EOL;
$total_files = count($_FILES['arq']['name']);
for ($i=0; $i<$total_files; $i++) {
// foreach ($_FILES['arq']['tmp_name'] as $file) {
  print "file: " . $_FILES['arq']['tmp_name'][$i]. PHP_EOL;
  $contents = process_ca_txt($_FILES['arq']['tmp_name'][$i]);
  // $content = file_get_contents($_FILES['arq']['tmp_name'][$i]);
  foreach($contents as $line)
  {
    $cert = openssl_x509_parse($line);
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

      try {
        $stmt->execute(
          [
            ':pfx'        => null,
            ':cert'       => $line,
            ':pkey'       => null,
            ':pass'       => null,
            ':subject'    => $subject,
            ':issuer'     => ($subject == $issuer) ? null : $issuer,
            ':not_before' => date("Y/m/d", $cert['validFrom_time_t']),
            ':not_after'  => date("Y/m/d", $cert['validTo_time_t']),
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
  }
}
echo "</pre>";
