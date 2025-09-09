<?php
require_once __DIR__ . '/../vendor/autoload.php';

$SUPABASE_URL = getenv('SUPABASE_URL');
$SUPABASE_KEY = getenv('SUPABASE_SERVICE_ROLE') ?: getenv('SUPABASE_ANON_KEY');

if (!$SUPABASE_URL || !$SUPABASE_KEY) {
  http_response_code(500);
  die("Supabase env vars missing");
}

// Example using Guzzle directly against PostgREST
function sb_select($table, $query = []) {
  global $SUPABASE_URL, $SUPABASE_KEY;
  $client = new \GuzzleHttp\Client([
    'base_uri' => rtrim($SUPABASE_URL, '/') . '/rest/v1/',
    'headers' => [
      'apikey' => $SUPABASE_KEY,
      'Authorization' => 'Bearer ' . $SUPABASE_KEY,
      'Accept' => 'application/json'
    ]
  ]);
  $resp = $client->get($table, ['query' => array_merge($query, ['select' => '*'])]);
  return json_decode($resp->getBody(), true);
}

function sb_insert($table, $data) {
  global $SUPABASE_URL, $SUPABASE_KEY;
  $client = new \GuzzleHttp\Client([
    'base_uri' => rtrim($SUPABASE_URL, '/') . '/rest/v1/',
    'headers' => [
      'apikey' => $SUPABASE_KEY,
      'Authorization' => 'Bearer ' . $SUPABASE_KEY,
      'Accept' => 'application/json',
      'Content-Type' => 'application/json',
      'Prefer' => 'return=representation'
    ]
  ]);
  $resp = $client->post($table, ['body' => json_encode($data)]);
  return json_decode($resp->getBody(), true);
}
