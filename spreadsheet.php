<?php
/**
 * spreadsheetに書き込んだり、読み込んだりするやつ
 */

class Libs_Spreadsheet {

    // GIDを投げるための基数（固定です）(旧スプレッドシート)
    const RADIX_NUMBER = 31578;
    // GIDを投げるための基数（固定です）(新スプレッドシート)
    const NEW_RADIX_NUMBER = 474;

    // 以下　configに逃すなりなんなりとしてください。

    // GOOGLEID
    const GOOGLE_ID  = 'google_id';
    // PASSWD
    const GOOGLE_PASSWD  = 'google_password';
    public function __construct() {}

    /**
     * スプレッドシートに書き込む
     *
     * @access  public
     * @params  $sheets_id /d/17t6TQW-9beLHjiFTnHKMQz2UFHaRt1oqyCaBuZ1gpSQ/の17t6TQW-9beLHjiFTnHKMQz2UFHaRt1oqyCaBuZ1gpSQの部分
     * @params  $sheets_gid  gid=216156014の216156014の部分
     * @params  $params      array 入れたいデータの連想配列
     * @params  $sheets_type int 新スプレッドシート：１　旧スプレッドシート：０
     * @return  bool
     */
    public static function insert_sheet($sheets_id, $sheets_gid, $params, $sheets_type=1) 
    {
        // トークン取得
        try {
            $token = self::_get_google_token();
         } catch (Exception $e) {
            \Log::e('Error: Google ClientLogin Token failed. '.$e->getMessage());
            return false;
         }
        // GIDをAPI用に変換 
        $gid_string = self::_convert_gid_to_string($sheets_gid, $sheets_type);

        // XMLヘッダと内容取得
        $header = self::_create_xml_header($token);
        $fields = self::_create_xml_fields($params); 

        // ターゲットURL取得
        $url = self::_send_target_url($sheets_id, $gid_string);
        // 送信
        try {
            self::_send($url, $header, $fields);
         } catch (Exception $e) {
            \Log::e('Error: Google Spreadsheet send data. '.$e->getMessage());
            return false;
         }

    }

    // CSVとしてspreadsheetを取得する
    public static function get_sheet($sheets_id, $sheets_gid, $sheets_type=1)
    {
        // トークン取得
        try {
            $token = self::_get_google_token();
         } catch (Exception $e) {
            \Log::e('Error: Google ClientLogin Token failed. '.$e->getMessage());
            return false;
         }

        // ヘッダ生成
        $headers = self::_create_xml_header($token);

        // 取得先URL生成
        $url = self::_get_csv_target_url($sheets_id, $sheets_gid);

        try {
            $csv = self::_get_csv($url, $headers);
         } catch (Exception $e) {
            \Log::e('Error: Google Spreadsheet GET CSV failed.. '.$e->getMessage());
            return false;
         }
         return  $csv;
    }

    // トークン取得
    private static function _get_google_token() {

        $url = 'https://www.google.com/accounts/ClientLogin';
        $fields = array(
            'Email' => self::GOOGLE_ID,
            'Passwd' => self::GOOGLE_PASSWD,
            'accountType' => 'HOSTED_OR_GOOGLE',
            'service' => 'wise',
            'source' => 'pfbc'
        );

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $fields);
        $response = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($status == 200) {
            // 認証成功
            if (stripos($response, 'auth=') !== false) {
                preg_match("/auth=([a-z0-9_\-]+)/i", $response, $matches);
                $token = $matches[1];
                return $token;
            // 認証失敗
            } else {
                return false;
            }
        // 認証失敗
        } else {
            return false;
        }
    }

    private static function _get_csv($url, $headers) {

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if($status == 200) {
            return $response;
        } else {
            return false;
        }
    }

    // XML送信（書き込み）
    private static function _send($url, $headers, $fields) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $fields);
        $response = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        if($status == 200) {
            return true;
        } else {
            return false;
        }
    }

    // 送信先URLを作る
    private static function _send_target_url($sid, $gid) {
        $url = 'https://spreadsheets.google.com/feeds/list/'.$sid.'/'.$gid.'/private/full';
        return $url;

    }

    // CSV取得先URLを作る
    private static function _get_csv_target_url($sid, $gid) {
        $url = 'https://docs.google.com/spreadsheets/d/'.$sid.'/export?format=csv&id='.$sid.'&gid='.$gid.'';
        return $url;
    }

    // シート番号を変換 注意　新スプレッドシートと旧スプレッドシートは基数が違う
    private static function _convert_gid_to_string($gid, $is_new=0) 
    {

        if (!$is_new) {
            // 古いシート
            $string = (string)$gid ^ self::RADIX_NUMBER;
            return base_convert($string, 10, 36);
        } else {
            // 新しいシート
            $string = (string)$gid ^ self::NEW_RADIX_NUMBER;
            $base = base_convert($string, 10, 36);
            return "o" . $base;

        }
    }

    // XMLヘッダ
    private static function _create_xml_header($token) {

        $headers = array(
            'Content-Type: application/atom+xml',
            'Authorization: GoogleLogin auth=' . $token,
            'GData-Version: 3.0'
        );
        return $headers;
    }

    // XML中身　送るデータ
    private static function _create_xml_fields($params) {

        $fields = '<entry xmlns="http://www.w3.org/2005/Atom" xmlns:gsx="http://schemas.google.com/spreadsheets/2006/extended">';

        foreach ($params as $key => $value) {
            $fields .= "<gsx:$key><![CDATA[$value]]></gsx:$key>";
        }
        $fields .= '</entry>';
        return $fields;
    }
}

