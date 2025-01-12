<?php
include_once "conf.php";
?><!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cert Mgmt</title>
<style type="text/css" media="all">
#flex-container {
    display: flex;
}
#form_section {
    padding: 1% 5%;
}
#cert_list, #cert_list td, #cert_list th {
    border: solid 1px #999;
    padding: 0.3rem;
}
#cert_list>thead>tr {
    background-color: #bbb;
}
#cert_list {
    border-collapse: collapse;
}
.cn-tag {
    list-style: circle;
    list-style-position: inside;
    padding: 0;
    margin: 5px 0;
}
.cn-tag>li {
    /* display: inline-block; */
    background-color: #eee;
    padding: 1px 4px;
    margin: 0 4px;
    /* text-transform: underline; */
}
</style>
</head>
<body>
<div id="flex-container">
    <section id="form_section">
        <h2>Certs Management</h2>
        <form id='form' action="conv.php" method="post" enctype="multipart/form-data">
          <div>
              <div>
                  <label for="cert">Selecione um arquivo:</label>
              </div>
              <input required type="file" id="arq" name="arq">
          </div>
          <div>
              <div>
                <label class="fs-4 text" for="pass">Senha (passphrase) do certficado PFX</label>
              </div>
              <input type="password" name="pass" id="pass" value="" placeholder="Password">
          </div>
        </form>
        <button onclick="sendFile()">Send</button>
<?php 
// try {
//     $query = $pdo->prepare("SELECT id, subject, issuer, not_after, json_extract(raw, '$.extensions.subjectAltName') as alt_name FROM cert WHERE pfx IS NOT NULL ORDER BY id");
//     $query->execute();
//     $result = $query->fetchAll(PDO::FETCH_ASSOC);
// } catch (PDOException $e) {
//     print "Error: " . $e->getMessage();
//     exit(2);
// }

// Parameters
$text = $_GET['text']??Null;
$result = [];
if ($text) {
  $text = "%".$text."%";
  try {
    $stmt = $pdo->prepare(
      <<<EOT
  SELECT id,
    subject,
    issuer,
    not_after,
    json_extract(raw, '$.extensions.subjectAltName') as alt_name
  FROM cert WHERE json_extract(raw, '$.extensions.subjectAltName') like :text
  EOT
  );
    $stmt->execute([
      ':text' => $text,
    ]);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
  } catch (PDOException $e) {
    print "Error: " . $e->getMessage();
    exit(2);
  }
}

?>
    <h3>Certificates:</h3>
    <form id="search_form" action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="get">
      <input type="text" name="text" id="search_field">
      <input type="submit" name="submit" id="submit_search" value="search">
    </form>
    <table id="cert_list">
    <thead>
    <tr>
    <th>ID</th>
    <th>SUBJECT</th>
    <th>ALTNAMES</th>
    <th>EXPIRE</th>
    <th>ISSUER</th>
    </tr>
    </thead>
    <tbody>

<?php
foreach ($result as $line) {
    print "<tr>";
    echo "<td><button onclick='get_cert(\"api/get-cert/?id={$line['id']}\")'>{$line['id']}</button></td>";
    echo "<td><pre>",strtr($line['subject'], " ", "\n"),"</pre></td>";
    echo "<td><pre>",strtr($line['alt_name'], " ", "\n"), "</pre></td>";
    echo "<td><pre>{$line['not_after']}</pre></td>";
    echo "<td><pre>",strtr($line['issuer'], " ", "\n"), "</pre></td>";
    print "</tr>";
}
?>
    </tbody>
    </table>
    </section>
    <section id="result_panel">
    <h3>CN: <span id="result_cert_title"></span></h3>
    <h4>Certificate:</h4>
    <textarea name="result_cert" id="result_cert" rows="15" cols="68"></textarea>
    <h4>Private Key:</h4>
    <textarea name="result_pkey" id="result_pkey" rows="15" cols="68"></textarea>
    <h4>Full Chain:</h4>
    <div><ul id="result_chain" class="cn-tag"></small></ul>
    <textarea name="result_fullchain" id="result_fullchain" rows="15" cols="68"></textarea>
    </section>
    </div>
    <script src="js/axios.min.js" charset="utf-8"></script>
    <script>
        function sendFile() {
          form = document.getElementById("form")
          arq = document.querySelector("#arq")
          if (arq.value) {
            // for (f of arq.files) {
            //         console.log(f)
            // }
            form.submit(function (e) {
                // event handler
            });
          }
        }
        function get_cert(qstr) {
        axios.get( qstr )
          .then((response) => {
          console.log(response.data)
            result_cert_title = document.querySelector("#result_cert_title");
            result_pkey = document.querySelector("#result_pkey");
            result_cert = document.querySelector("#result_cert");
            result_fullchain = document.querySelector("#result_fullchain");
            result_chain = document.querySelector("#result_chain");
            result_fullchain.value = '';
            result_chain.innerHTML = '';

            result_cert_title.innerHTML = response.data[0].subject;
            result_cert.value = response.data[0].cert;
            result_pkey.value = response.data[0].pkey;

            for (line of response.data) {
              result_fullchain.value += line.cert + "\n";
              result_chain.innerHTML += "<li>"+ line.subject + "</li>";
            }
          })
          .catch((error) => {
              console.log(error);
          });
        }
    </script>
</body>
</html>
