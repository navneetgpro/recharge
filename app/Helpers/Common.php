<?php
namespace App\Helpers;

class Common{
    public static function generateReferral($length = 8){
        return \Str::random($length);
    }

    public static function numericPlanDays($text,$string){
        $text = "28 Days";
        $days = str_ireplace($remove,"",$text);
        return preg_replace('/\s+/', '', $days);
    }

    public static function pctDiscount($amount,$discountPct,$object=true){
        $disAmt = ($discountPct / 100) * $amount;
        if(!$object) return $disAmt;
        $discountedAmount = ($amount-$disAmt);
        return (Object) ['discount'=>$disAmt,'amount'=>$discountedAmount];
    }

    public static function curl($url , $method='GET', $parameters, $header, $data=[]){
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
        curl_setopt($curl, CURLOPT_ENCODING, "");
        curl_setopt($curl, CURLOPT_TIMEOUT, 180);
        curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
        if($parameters != ""){
            curl_setopt($curl, CURLOPT_POSTFIELDS, $parameters);
        }

        if(sizeof($header) > 0){
            curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        }
        
        $response = curl_exec($curl);
        $err = curl_error($curl);
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        $apilog = null;
        if(isset($data[0])){
            try {
                $apilog = \App\Models\Apilog::create([
                    "api_id" => $data[0],
                    "txnid" => $data[1],
                    "response" => $response
                ]);
            } catch (\Exception $e) { }
        }

        return ["response" => $response, "error" => $err, 'code' => $code,'apilog' => $apilog];
    }

    public static function encrypt($plainText) {
        $key=env('PASSWORD_KEY');
        $secretKey = self::hextobin(md5($key));
        $initVector = pack("C*", 0x00, 0x01, 0x02, 0x03, 0x04, 0x05, 0x06, 0x07, 0x08, 0x09, 0x0a, 0x0b, 0x0c, 0x0d, 0x0e, 0x0f);
        $openMode = openssl_encrypt($plainText, 'AES-128-CBC', $secretKey, OPENSSL_RAW_DATA, $initVector);
        $encryptedText = bin2hex($openMode);
        return $encryptedText;
    }

    public static function validateEncrypt($encrypted,$plainText){
        if(self::decrypt($encrypted)==$plainText) return true;
        return false;
    }
    
    public static function decrypt($encryptedText) {
        $key=env('PASSWORD_KEY');
        $key = self::hextobin(md5($key));
        $initVector = pack("C*", 0x00, 0x01, 0x02, 0x03, 0x04, 0x05, 0x06, 0x07, 0x08, 0x09, 0x0a, 0x0b, 0x0c, 0x0d, 0x0e, 0x0f);
        $encryptedText = self::hextobin($encryptedText);
        $decryptedText = openssl_decrypt($encryptedText, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $initVector);
        return $decryptedText;
    }

    public static function hextobin($hexString) {
        $length = strlen($hexString);
        $binString = "";
        $count = 0;
        while ($count < $length) {
            $subString = substr($hexString, $count, 2);
            $packedString = pack("H*", $subString);
            if ($count == 0) {
                $binString = $packedString;
            } else {
                $binString .= $packedString;
            }
    
            $count += 2;
        }
        return $binString;
    }
}