<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Ticker;

class ApiController extends Controller
{
  public function getInfo(){
    $info = collect($this->api('getInfo')['return']);
    $info['total_balance'] = collect($info['balance_hold'])->map(function ($item, $key) use($info){
      return $info['balance'][$key] + $item;
    });
    // dd($info);
    return $info;
  }

  public function getAllTicker(){
    $tickerModel = Ticker::get()->last();
    // dd(collect($tickerModel->ticker)["btc_idr"]);
    $pair = [
      'btc_idr',
      // 'abyss_idr',
      // 'act_idr',
      // 'ada_idr',
      // 'aoa_idr',
      // 'bcd_idr',
      // 'bchabc_idr',
      // 'bchsv_idr',
      // 'btg_idr',
      // 'bts_idr',
      // 'dax_idr',
      // 'drk_idr',
      // 'doge_idr',
      // 'eth_idr',
      // 'etc_idr',
      // 'gsc_idr',
      // 'gxs_idr',
      // 'ignis_idr',
      // 'ltc_idr',
      // 'npxs_idr',
      // 'nxt_idr',
      // 'ont_idr',
      // 'stq_idr',
      // 'scc_idr',
      // 'sumo_idr',
      // 'ten_idr',
      // 'trx_idr',
      // 'usdt_idr',
      // 'vex_idr',
      // 'waves_idr',
      // 'nem_idr',
      // 'str_idr',
      // 'xrp_idr',
      // 'xzc_idr',
    ];
    foreach ($pair as $value) {
      $ticker[$value] = $this->getTicker($value);
    }
    // $ticker = json_encode($ticker);
    // $tickerModel = new Ticker;
    // $tickerModel->ticker = $ticker;
    // $tickerModel->save();
    return $ticker["btc_idr"]["ticker"]["last"];
  }

  public function getTicker($pair='btc_idr'){
    static $ch = null;
    if (is_null($ch)) {
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; INDODAXCOM PHP client;
      '.php_uname('s').'; PHP/'.phpversion().')');
    }
    curl_setopt($ch, CURLOPT_URL, "https://indodax.com/api/$pair/ticker");
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    $res = curl_exec($ch);
    if ($res === false) throw new Exception('Could not get reply: '.curl_error($ch));
    $dec = json_decode($res, true);
    return $dec;
    // return file_get_contents("https://indodax.com/api/$pair/ticker");
  }

  private function api($method, array $req = array()) {
    // API settings
    $key = 'ZEOOXAHA-CGCESXZS-KCOPFBEH-KYZ7NOCW-8FABHM36'; // your API-key
    $secret = 'ef6f65012ae061c987ba50bb1d8a9d2dc04eac46783948382dbb37ffe65b6d7f9d666cf545e58253'; // your Secret-key
    $req['method'] = $method;
    $req['nonce'] = time();
    // generate the POST data string
    $post_data = http_build_query($req, '', '&');
    $sign = hash_hmac('sha512', $post_data, $secret);
    // generate the extra headers
    $headers = array(
      'Sign: '.$sign,
      'Key: '.$key,
    );
    // our curl handle (initialize if required)
    static $ch = null;
    if (is_null($ch)) {
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; INDODAXCOM PHP client;
      '.php_uname('s').'; PHP/'.phpversion().')');
    }
    curl_setopt($ch, CURLOPT_URL, 'https://indodax.com/tapi/');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    // run the query
    $res = curl_exec($ch);
    if ($res === false) throw new Exception('Could not get reply: '.curl_error($ch));
    $dec = json_decode($res, true);
    if (!$dec) throw new Exception('Invalid data received, please make sure connection is working and
    requested API exists: '.$res);
    curl_close($ch);
    $ch = null;
    return collect($dec);
  }

  public function webhook(){
    $webhookurl = "https://discordapp.com/api/webhooks/514335956183023616/KBxjI_DAUK6dvzMztxx5Zazsve7H014HFDPIWwPPAKGAXZ4AenPNhE3fIq5M5raUI06M";
    $info = $this->getInfo();
    $jenisSaldo = '';
    $totalSaldo = '';
    $estimasiSaldo = '';
    $totalAsset = 0;
    foreach ($info['total_balance'] as $key=>$value) {
      $jenisSaldo .= strtoupper($key)." \n";
      $totalSaldo .= "$value \n";
      $saldo = ($this->getTicker($key."_idr")["ticker"]["last"]??1)*$value;
      $estimasiSaldo .="Rp. ".number_format($saldo)."\n";
      $totalAsset += $saldo;
    }
    $json_data = [
      "username" => "gemBot Notif",
      "avatar_url" => "https://miro.medium.com/max/480/1*paQ7E6f2VyTKXHpR-aViFg.png",
      "embeds" => [[
        "title" => "Notification Info Indodax",
        "color" => 0x44e0ff,
        "content" => "Jumlah Nilai Asset Kita : **Rp. ".number_format($totalAsset)."**",
        "fields" => [
          [
            "name" => "Total Nilai Asset",
            "value" => "Jumlah Nilai Asset Kita : **Rp. ".number_format($totalAsset)."**",
            "inline" => false
          ],
          [
            "name" => "Jenis Asset",
            "value" => "$jenisSaldo",
            "inline" => true
          ],
          [
            "name" => "Jumlah Saldo",
            "value" => "$totalSaldo",
            "inline" => true
          ],
          [
            "name" => "Estimasi Nilai Asset",
            "value" => "$estimasiSaldo",
            "inline" => true
          ]
        ]
        ]]
      ];
      $make_json = json_encode($json_data);
      $ch = curl_init( $webhookurl );
      curl_setopt( $ch, CURLOPT_POST, 1);
      curl_setopt( $ch, CURLOPT_POSTFIELDS, $make_json);
      curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1);
      curl_setopt( $ch, CURLOPT_HEADER, 0);
      curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1);
      return curl_exec( $ch );
    }
  }
