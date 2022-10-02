<?php

function get($key, $default=NULL) {
  return array_key_exists($key, $_GET) ? $_GET[$key] : $default;
}

function session($key, $default=NULL) {
  return array_key_exists($key, $_SESSION) ? $_SESSION[$key] : $default;
}



function doApiRequest($url, $post=FALSE, $headers=array(), $returnjson=TRUE, $returnstatuscodeandheaders=FALSE) {
  $ch = curl_init($url);
  curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

  if ($returnstatuscodeandheaders) { curl_setopt($ch, CURLOPT_HEADER, 1); }

  $response = curl_exec($ch);


  if($post)
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));

  if (str_contains($url, 'discord.com')) {
    $headers[] = 'Accept: application/json';

    if(session('access_token'))
      $headers[] = 'Authorization: Bearer ' . session('access_token');
  }

  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

  $headers = [];
  // this function is called by curl for each header received
  //thx https://stackoverflow.com/a/41135574
  curl_setopt($ch, CURLOPT_HEADERFUNCTION,
    function($curl, $header) use (&$headers) {
      $len = strlen($header);
      $header = explode(':', $header, 2);
      if (count($header) < 2) // ignore invalid headers
        return $len;

      $headers[strtolower(trim($header[0]))][] = trim($header[1]);
      
      return $len;
    }
  );

  $response = curl_exec($ch);



  if ($returnstatuscodeandheaders) {
    preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $response, $matches);
    $cookies = array();
    foreach($matches[1] as $item) {
        parse_str($item, $cookie);
        $cookies = array_merge($cookies, $cookie);
    }

    class Response {}
    $r = new Response();
    $r->httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $r->headers = $headers;
    $r->cookies = $cookies;

    return $r;
  } else {
    if ($returnjson) {
      return json_decode($response);
    } else {
      return $response;
    }
  }
}

?>