<?php

function get_ca_certs_from_mozilla(string $type='csv', string $ca_certs_file = "ca_certificates-mozilla") :void
{
  if ($type == 'csv')
  {
    $ca_certs_file .= '.csv';
    $mozilla_url =  "https://ccadb.my.salesforce-sites.com/mozilla/IncludedRootsPEMCSV?TrustBitsInclude=Websites";

  } else if ($type == 'txt')
  {
    $ca_certs_file .= '.txt';
    $mozilla_url =  "https://ccadb.my.salesforce-sites.com/mozilla/IncludedRootsPEMTxt?TrustBitsInclude=Websites";
  }

  $ch = curl_init($mozilla_url);

  $fp = fopen($ca_certs_file, "w"); // File for cURL result

  curl_setopt($ch, CURLOPT_FILE, $fp);
  curl_setopt($ch, CURLOPT_HEADER, 0);

  curl_exec($ch);
  if(curl_error($ch)) {
      fwrite($fp, curl_error($ch));
  }
  curl_close($ch);
  fclose($fp);
}

function process_csv(string $ca_certs_file = "ca_certificates-mozilla.csv", $header=true) :array
{
  $result = ['header' => '', 'rows' => []];

  $f_ca = fopen($ca_certs_file, "r");

  if ($header) {
    if (($cas = fgetcsv($f_ca)) !== FALSE)
    {
      $result['header'] = $cas;
    }
  }

  while (($cas = fgetcsv($f_ca)) !== FALSE)
  {
    $result['rows'][] = $cas;
    // echo trim($cas[0], "'"). PHP_EOL;
  }

  fclose($f_ca);

  return $result;
}

function process_ca_txt( string $ca_certs_file = "ca_certificates-mozilla.txt") :array
{
  $result = [];

  $file = fopen($ca_certs_file, 'r');

  while ( ($line = fgets($file) ) !== false )
  {
    $cert = "";
    if ("-----BEGIN CERTIFICATE-----" == rtrim($line))
    {
      $cert .= $line;

      while ( ($line = fgets($file) ) !== false )
      {
        $cert .= $line;
        if ("-----END CERTIFICATE-----" == rtrim($line))
        {
          break;
        }
      }
      $result[] = $cert;

      // if ($cert != "")
      // {
      //   if ($data = openssl_x509_parse($cert))
      //   {
      //     $result[] = $data;
      //   }
      // }
    }
  }

  fclose($file);

  return $result;
}

function process_csv_cb(string $ca_certs_file = "ca_certificates-mozilla.csv", $header=true) :array
{
  $result = ['header' => [], 'rows' => []];

  $f_ca = fopen($ca_certs_file, "r");

  if ($header) {
    if (($cas = fgetcsv($f_ca)) !== FALSE)
    {
      $result['header'] = $cas;
    }
  }

  while (($cas = fgetcsv($f_ca)) !== FALSE)
  {
    $result['rows'][] = $cas;
    // echo trim($cas[0], "'"). PHP_EOL;
  }

  fclose($f_ca);

  return $result;
}

