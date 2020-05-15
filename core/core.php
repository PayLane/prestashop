<?php

/*
 * 2005-2016 PayLane sp. z.o.o.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@Paylane.pl so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PayLane to newer
 * versions in the future. If you wish to customize PayLane for your
 * needs please refer to http://www.Paylane.pl for more information.
 *
 *  @author PayLane <info@paylane.pl>
 *  @copyright  2005-2019 PayLane sp. z.o.o.
 *  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PayLane sp. z.o.o.
 */

class PaylanePaymentCore
{
    protected static $paylaneSecureFormUrl = 'https://secure.paylane.com/order/cart.html';
    protected static $paylaneApiUrl = '';

    private $paylane;

    public function __construct(Module $paylane) {
        $this->paylane = $paylane;
    }

    public static $allowedCountries = array(
        'ALA','ALB','DZA','ASM','AND','AGO','AIA','ATA','ATG','ARG','ARM','ABW','AUS','AUT','AZE','BHS','BHR','BGD',
        'BRB','BLR','BEL','BLZ','BEN','BMU','BTN','BOL','BIH','BWA','BVT','BRA','BRN','BGR','BFA','BDI','KHM','CMR',
        'CAN','CPV','CYM','CAF','TCD','CHL','CHN','CXR','CCK','COL','COM','COG','COD','COK','CRI','CIV','HRV','CYP',
        'CZE','DNK','DJI','DMA','DOM','ECU','EGY','SLV','GNQ','ERI','EST','ETH','FLK','FRO','FJI','FIN','FRA','GUF',
        'PYF','ATF','GAB','GMB','GEO','DEU','GHA','GIB','GRC','GRL','GRD','GLP','GUM','GTM','GGY','HTI','HMD','VAT',
        'GIN','GNB','GUY','HND','HKG','HUN','ISL','IND','IDN','IRL','IMN','ISR','ITA','JAM','JPN','JEY','JOR','KAZ',
        'KEN','KIR','KOR','KWT','LAO','LVA','LBN','LSO','LBR','LIE','LTU','LUX','MAC','MKD','MDG','MWI','MYS','MDV',
        'MLI','MLT','MHL','MTQ','MRT','MUS','MYT','MEX','FSM','MDA','MCO','MNG','MNE','MSR','MAR','MOZ','MMR','NAM',
        'NPL','NLD','ANT','NCL','NZL','NIC','NER','NGA','NIU','NFK','MNP','NOR','OMN','PAK','PLW','PSE','PAN','PNG',
        'PRY','PER','PHL','PCN','POL','PRT','PRI','QAT','REU','ROU','RUS','RWA','SHN','KNA','LCA','MAF','SPM','VCT',
        'WSM','SMR','STP','SAU','SEN','SRB','SYC','SLE','SGP','SVK','SVN','SLB','SOM','ZAF','SGS','ESP','LKA','SUR',
        'SJM','SWZ','SWE','CHE','TWN','TJK','TZA','THA','TLS','TGO','TKL','TON','TTO','TUN','TUR','TKM','TCA','TUV',
        'UGA','UKR','ARE','GBR','USA','UMI','URY','UZB','VUT','VEN','VNM','VGB','VIR','WLF','ESH','YEM','ZMB','ZWE'
    );

    public static $unallowedCountries = array(
        //        'AFG','CUB','ERI','IRN','IRQ','JPN','KGZ','LBY','PRK','SDN','SSD','SYR'
    );

    public static $paymentMethods = array (
        'SECUREFORM' => array(
            'name' => 'Paylane SecureForm',
            'allowedCountries' => 'ALL'
        ),
        'CREDITCARD' => array(
            'name' => 'Paylane CreditCard',
            'allowedCountries' => 'ALL'
        ),
        'BANKTRANSFER' => array(
            'name' => 'Paylane BankTransfer',
            'allowedCountries' => 'ALL'
        ),
        'PAYPAL' => array(
            'name' => 'Paylane PayPal',
            'allowedCountries' => 'ALL'
        ),
        'BLIK' => array(
            'name' => 'Paylane BLIK',
            'allowedCountries' => 'ALL',
        ),
        // 'DIRECTDEBIT' => array(
        //     'name' => 'Paylane DirectDebit',
        //     'allowedCountries' => 'ALL'
        // ),
        // 'SOFORT' => array(
        //     'name' => 'Paylane Sofort',
        //     'allowedCountries' => 'ALL'
        // ),
        // 'IDEAL' => array(
        //     'name' => 'Paylane Ideal',
        //     'allowedCountries' => 'ALL'
        // ),
        // 'APPLEPAY' => array(
        //     'name' => 'Paylane ApplePay',
        //     'allowedCountries' => 'ALL'
        // ),
        // 'GOOGLEPAY' => array(
        //     'name' => 'Paylane GooglePay',
        //     'allowedCountries' => 'ALL'
        // ),
    );

    public static function getPaymentMethods()
    {
        return self::$paymentMethods;
    }

    public static function getPaymentMethodByPaymentType($paymentType)
    {
        return self::$paymentMethods[$paymentType];
    }

    public static function getPaylaneRedirectSecureFormUrl()
    {
        $paylaneRedirectUrl = self::$paylaneSecureFormUrl;
        return $paylaneRedirectUrl;
    }

    public static function getPaylaneRedirectUrl()
    {
        $paylaneRedirectUrl = self::$paylaneApiUrl;
        return $paylaneRedirectUrl;
    }

    public static function paymentStatus($paylaneStatus)
    {
        $status = array();
        $status['PENDING'] = '1';
        //$status['PERFORMED'] = '2';
        $status['PERFORMED'] = '3';
        $status['CLEARED'] = '3';
        $status['ERROR'] = '-2';

        return isset($status[$paylaneStatus]) ? $status[$paylaneStatus] : $status['ERROR'];
    }

    public static function getSupportedPaymentsByCountryCode($countryCode)
    {
        if (Tools::strlen($countryCode) == 2) {
            $countryCode = self::getCountryIso3ByIso2($countryCode);
        }

        $supportedPayments = array();

        if (!in_array($countryCode, self::$unallowedCountries)) {
            foreach (self::$paymentMethods as $key => $paymentMethod) {
                if (isset($paymentMethod['exceptedCountries'])
                    && in_array($countryCode, $paymentMethod['exceptedCountries'])
                ) {
                    continue;
                }
                if ($paymentMethod['allowedCountries'] == 'ALL') {
                    $paymentMethod['allowedCountries'] = self::$allowedCountries;
                }
                if (in_array($countryCode, $paymentMethod['allowedCountries'])) {
                    $supportedPayments[] =  $key;
                }
            }
        }
        return $supportedPayments;
    }

    public static function getDateTime()
    {
        $t = microtime(true);
        $micro = sprintf("%06d", ($t - floor($t)) * 1000000);
        $d = new DateTime(date('Y-m-d H:i:s.'.$micro, $t));

        return $d->format("ymdhiu");
    }

    public static function randomNumber($length)
    {
        $result = '';

        for ($i = 0; $i < $length; $i++) {
            $result .= mt_rand(0, 9);
        }

        return $result;
    }

    public static function getTrnStatus($code)
    {
        switch ($code) {
        case '3':
            $status = 'BACKEND_TT_CLEARED';
            break;
        case '2':
            $status = 'BACKEND_TT_PERFORMED';
            break;
        case '1':
            $status = 'BACKEND_TT_PENDING';
            break;
        case '-2':
            $status = 'BACKEND_TT_FAILED';
            break;
        }
        return $status;
    }

    public static function getErrorMessage($responseStatus) { 
        $errorStatus = ('Paylane Error:');
        $api_url = 'https://payto.app/api/translate_error/pl/';
        
        if (isset($responseStatus['error_code'])) { //Secureform, BankTransfer 
            $error_code = $api_url . $responseStatus['error_code'];
            $translatedErrorCode = file_get_contents($error_code);
            $errorStatus .= ' '. $translatedErrorCode;
        }
        // if (isset($responseStatus['error_code'])) {
        //     $errorStatus .= ' '. $responseStatus['error_code'];
        // }
        if (isset($responseStatus['error_text'])) {
            $errorStatus .= ' '. $responseStatus['error_text'];
        }
        
        return $errorStatus; 
    }

    public static function getCountryIso3ByIso2($iso2)
    {
        $iso3 = array(
            'AF' => 'AFG',
            'AL' => 'ALB',
            'DZ' => 'DZA',
            'AS' => 'ASM',
            'AD' => 'AND',
            'AO' => 'AGO',
            'AI' => 'AIA',
            'AQ' => 'ATA',
            'AG' => 'ATG',
            'AR' => 'ARG',
            'AM' => 'ARM',
            'AW' => 'ABW',
            'AU' => 'AUS',
            'AT' => 'AUT',
            'AZ' => 'AZE',
            'BS' => 'BHS',
            'BH' => 'BHR',
            'BD' => 'BGD',
            'BB' => 'BRB',
            'BY' => 'BLR',
            'BE' => 'BEL',
            'BZ' => 'BLZ',
            'BJ' => 'BEN',
            'BM' => 'BMU',
            'BT' => 'BTN',
            'BO' => 'BOL',
            'BA' => 'BIH',
            'BW' => 'BWA',
            'BV' => 'BVT',
            'BR' => 'BRA',
            'IO' => 'IOT',
            'VG' => 'VGB',
            'BN' => 'BRN',
            'BG' => 'BGR',
            'BF' => 'BFA',
            'BI' => 'BDI',
            'KH' => 'KHM',
            'CM' => 'CMR',
            'CA' => 'CAN',
            'CV' => 'CPV',
            'KY' => 'CYM',
            'CF' => 'CAF',
            'TD' => 'TCD',
            'CL' => 'CHL',
            'CN' => 'CHN',
            'CX' => 'CXR',
            'CC' => 'CCK',
            'CO' => 'COL',
            'KM' => 'COM',
            'CG' => 'COG',
            'CD' => 'COD',
            'CK' => 'COK',
            'CR' => 'CRI',
            'HR' => 'HRV',
            'CU' => 'CUB',
            'CY' => 'CYP',
            'CZ' => 'CZE',
            'CI' => 'CIV',
            'DK' => 'DNK',
            'DJ' => 'DJI',
            'DM' => 'DMA',
            'DO' => 'DOM',
            'EC' => 'ECU',
            'EG' => 'EGY',
            'SV' => 'SLV',
            'GQ' => 'GNQ',
            'ER' => 'ERI',
            'EE' => 'EST',
            'ET' => 'ETH',
            'FK' => 'FLK',
            'FO' => 'FRO',
            'FJ' => 'FJI',
            'FI' => 'FIN',
            'FR' => 'FRA',
            'GF' => 'GUF',
            'PF' => 'PYF',
            'TF' => 'ATF',
            'GA' => 'GAB',
            'GM' => 'GMB',
            'GE' => 'GEO',
            'DE' => 'DEU',
            'GH' => 'GHA',
            'GI' => 'GIB',
            'GR' => 'GRC',
            'GL' => 'GRL',
            'GD' => 'GRD',
            'GP' => 'GLD',
            'GU' => 'GUM',
            'GT' => 'GTM',
            'GG' => 'GGY',
            'GN' => 'HTI',
            'GW' => 'HMD',
            'GY' => 'VAT',
            'HT' => 'GIN',
            'HM' => 'GNB',
            'HN' => 'HND',
            'HK' => 'HKG',
            'HU' => 'HUN',
            'IS' => 'ISL',
            'IN' => 'IND',
            'ID' => 'IDN',
            'IR' => 'IRN',
            'IQ' => 'IRQ',
            'IE' => 'IRL',
            'IM' => 'IMN',
            'IL' => 'ISR',
            'IT' => 'ITA',
            'JM' => 'JAM',
            'JP' => 'JPN',
            'JE' => 'JEY',
            'JO' => 'JOR',
            'KZ' => 'KAZ',
            'KE' => 'KEN',
            'KI' => 'KIR',
            'KW' => 'KWT',
            'KG' => 'KGZ',
            'LA' => 'LAO',
            'LV' => 'LVA',
            'LB' => 'LBN',
            'LS' => 'LSO',
            'LR' => 'LBR',
            'LY' => 'LBY',
            'LI' => 'LIE',
            'LT' => 'LTU',
            'LU' => 'LUX',
            'MO' => 'MAC',
            'MK' => 'MKD',
            'MG' => 'MDG',
            'MW' => 'MWI',
            'MY' => 'MYS',
            'MV' => 'MDV',
            'ML' => 'MLI',
            'MT' => 'MLT',
            'MH' => 'MHL',
            'MQ' => 'MTQ',
            'MR' => 'MRT',
            'MU' => 'MUS',
            'YT' => 'MYT',
            'MX' => 'MEX',
            'FM' => 'FSM',
            'MD' => 'MDA',
            'MC' => 'MCO',
            'MN' => 'MNG',
            'ME' => 'MNE',
            'MS' => 'MSR',
            'MA' => 'MAR',
            'MZ' => 'MOZ',
            'MM' => 'MMR',
            'NA' => 'NAM',
            'NR' => 'NRU',
            'NP' => 'NPL',
            'NL' => 'NLD',
            'AN' => 'ANT',
            'NC' => 'NCL',
            'NZ' => 'NZL',
            'NI' => 'NIC',
            'NE' => 'NER',
            'NG' => 'NGA',
            'NU' => 'NIU',
            'NF' => 'NFK',
            'KP' => 'PRK',
            'MP' => 'MNP',
            'NO' => 'NOR',
            'OM' => 'OMN',
            'PK' => 'PAK',
            'PW' => 'PLW',
            'PS' => 'PSE',
            'PA' => 'PAN',
            'PG' => 'PNG',
            'PY' => 'PRY',
            'PE' => 'PER',
            'PH' => 'PHL',
            'PN' => 'PCN',
            'PL' => 'POL',
            'PT' => 'PRT',
            'PR' => 'PRI',
            'QA' => 'QAT',
            'RO' => 'ROU',
            'RU' => 'RUS',
            'RW' => 'RWA',
            'RE' => 'REU',
            'BL' => 'BLM',
            'SH' => 'SHN',
            'KN' => 'KNA',
            'LC' => 'LCA',
            'MF' => 'MAF',
            'PM' => 'SPM',
            'WS' => 'WSM',
            'SM' => 'SMR',
            'SA' => 'SAU',
            'SN' => 'SEN',
            'RS' => 'SRB',
            'SC' => 'SYC',
            'SL' => 'SLE',
            'SG' => 'SGP',
            'SK' => 'SVK',
            'SI' => 'SVN',
            'SB' => 'SLB',
            'SO' => 'SOM',
            'ZA' => 'ZAF',
            'GS' => 'SGS',
            'KR' => 'KOR',
            'ES' => 'ESP',
            'LK' => 'LKA',
            'VC' => 'VCT',
            'SD' => 'SDN',
            'SR' => 'SUR',
            'SJ' => 'SJM',
            'SZ' => 'SWZ',
            'SE' => 'SWE',
            'CH' => 'CHE',
            'SY' => 'SYR',
            'ST' => 'STP',
            'TW' => 'TWN',
            'TJ' => 'TJK',
            'TZ' => 'TZA',
            'TH' => 'THA',
            'TL' => 'TLS',
            'TG' => 'TGO',
            'TK' => 'TKL',
            'TO' => 'TON',
            'TT' => 'TTO',
            'TN' => 'TUN',
            'TR' => 'TUR',
            'TM' => 'TKM',
            'TC' => 'TCA',
            'TV' => 'TUV',
            'UM' => 'UMI',
            'VI' => 'VIR',
            'UG' => 'UGA',
            'UA' => 'UKR',
            'AE' => 'ARE',
            'GB' => 'GBR',
            'US' => 'USA',
            'UY' => 'URY',
            'UZ' => 'UZB',
            'VU' => 'VUT',
            'VA' => 'GUY',
            'VE' => 'VEN',
            'VN' => 'VNM',
            'WF' => 'WLF',
            'EH' => 'ESH',
            'YE' => 'YEM',
            'ZM' => 'ZMB',
            'ZW' => 'ZWE',
            'AX' => 'ALA'
        );
        if ($iso2) {
            return array_key_exists($iso2, $iso3) ? $iso3[$iso2] : '';
        } else {
            return '';
        }
    }

    public static function getCountryIso2ByIso3($iso3)
    {
        $iso2 = array(
            'AFG' => 'AF',
            'ALB' => 'AL',
            'DZA' => 'DZ',
            'ASM' => 'AS',
            'AND' => 'AD',
            'AGO' => 'AO',
            'AIA' => 'AI',
            'ATA' => 'AQ',
            'ATG' => 'AG',
            'ARG' => 'AR',
            'ARM' => 'AM',
            'ABW' => 'AW',
            'AUS' => 'AU',
            'AUT' => 'AT',
            'AZE' => 'AZ',
            'BHS' => 'BS',
            'BHR' => 'BH',
            'BGD' => 'BD',
            'BRB' => 'BB',
            'BLR' => 'BY',
            'BEL' => 'BE',
            'BLZ' => 'BZ',
            'BEN' => 'BJ',
            'BMU' => 'BM',
            'BTN' => 'BT',
            'BOL' => 'BO',
            'BIH' => 'BA',
            'BWA' => 'BW',
            'BVT' => 'BV',
            'BRA' => 'BR',
            'IOT' => 'IO',
            'VGB' => 'VG',
            'BRN' => 'BN',
            'BGR' => 'BG',
            'BFA' => 'BF',
            'BDI' => 'BI',
            'KHM' => 'KH',
            'CMR' => 'CM',
            'CAN' => 'CA',
            'CPV' => 'CV',
            'CYM' => 'KY',
            'CAF' => 'CF',
            'TCD' => 'TD',
            'CHL' => 'CL',
            'CHN' => 'CN',
            'CXR' => 'CX',
            'CCK' => 'CC',
            'COL' => 'CO',
            'COM' => 'KM',
            'COG' => 'CG',
            'COD' => 'CD',
            'COK' => 'CK',
            'CRI' => 'CR',
            'HRV' => 'HR',
            'CUB' => 'CU',
            'CYP' => 'CY',
            'CZE' => 'CZ',
            'CIV' => 'CI',
            'DNK' => 'DK',
            'DJI' => 'DJ',
            'DMA' => 'DM',
            'DOM' => 'DO',
            'ECU' => 'EC',
            'EGY' => 'EG',
            'SLV' => 'SV',
            'GNQ' => 'GQ',
            'ERI' => 'ER',
            'EST' => 'EE',
            'ETH' => 'ET',
            'FLK' => 'FK',
            'FRO' => 'FO',
            'FJI' => 'FJ',
            'FIN' => 'FI',
            'FRA' => 'FR',
            'GUF' => 'GF',
            'PYF' => 'PF',
            'ATF' => 'TF',
            'GAB' => 'GA',
            'GMB' => 'GM',
            'GEO' => 'GE',
            'DEU' => 'DE',
            'GHA' => 'GH',
            'GIB' => 'GI',
            'GRC' => 'GR',
            'GRL' => 'GL',
            'GRD' => 'GD',
            'GLD' => 'GP',
            'GUM' => 'GU',
            'GTM' => 'GT',
            'GGY' => 'GG',
            'HTI' => 'GN',
            'HMD' => 'GW',
            'VAT' => 'GY',
            'GIN' => 'HT',
            'GNB' => 'HM',
            'HND' => 'HN',
            'HKG' => 'HK',
            'HUN' => 'HU',
            'ISL' => 'IS',
            'IND' => 'IN',
            'IDN' => 'ID',
            'IRN' => 'IR',
            'IRQ' => 'IQ',
            'IRL' => 'IE',
            'IMN' => 'IM',
            'ISR' => 'IL',
            'ITA' => 'IT',
            'JAM' => 'JM',
            'JPN' => 'JP',
            'JEY' => 'JE',
            'JOR' => 'JO',
            'KAZ' => 'KZ',
            'KEN' => 'KE',
            'KIR' => 'KI',
            'KWT' => 'KW',
            'KGZ' => 'KG',
            'LAO' => 'LA',
            'LVA' => 'LV',
            'LBN' => 'LB',
            'LSO' => 'LS',
            'LBR' => 'LR',
            'LBY' => 'LY',
            'LIE' => 'LI',
            'LTU' => 'LT',
            'LUX' => 'LU',
            'MAC' => 'MO',
            'MKD' => 'MK',
            'MDG' => 'MG',
            'MWI' => 'MW',
            'MYS' => 'MY',
            'MDV' => 'MV',
            'MLI' => 'ML',
            'MLT' => 'MT',
            'MHL' => 'MH',
            'MTQ' => 'MQ',
            'MRT' => 'MR',
            'MUS' => 'MU',
            'MYT' => 'YT',
            'MEX' => 'MX',
            'FSM' => 'FM',
            'MDA' => 'MD',
            'MCO' => 'MC',
            'MNG' => 'MN',
            'MNE' => 'ME',
            'MSR' => 'MS',
            'MAR' => 'MA',
            'MOZ' => 'MZ',
            'MMR' => 'MM',
            'NAM' => 'NA',
            'NRU' => 'NR',
            'NPL' => 'NP',
            'NLD' => 'NL',
            'ANT' => 'AN',
            'NCL' => 'NC',
            'NZL' => 'NZ',
            'NIC' => 'NI',
            'NER' => 'NE',
            'NGA' => 'NG',
            'NIU' => 'NU',
            'NFK' => 'NF',
            'PRK' => 'KP',
            'MNP' => 'MP',
            'NOR' => 'NO',
            'OMN' => 'OM',
            'PAK' => 'PK',
            'PLW' => 'PW',
            'PSE' => 'PS',
            'PAN' => 'PA',
            'PNG' => 'PG',
            'PRY' => 'PY',
            'PER' => 'PE',
            'PHL' => 'PH',
            'PCN' => 'PN',
            'POL' => 'PL',
            'PRT' => 'PT',
            'PRI' => 'PR',
            'QAT' => 'QA',
            'ROU' => 'RO',
            'RUS' => 'RU',
            'RWA' => 'RW',
            'REU' => 'RE',
            'BLM' => 'BL',
            'SHN' => 'SH',
            'KNA' => 'KN',
            'LCA' => 'LC',
            'MAF' => 'MF',
            'SPM' => 'PM',
            'WSM' => 'WS',
            'SMR' => 'SM',
            'SAU' => 'SA',
            'SEN' => 'SN',
            'SRB' => 'RS',
            'SYC' => 'SC',
            'SLE' => 'SL',
            'SGP' => 'SG',
            'SVK' => 'SK',
            'SVN' => 'SI',
            'SLB' => 'SB',
            'SOM' => 'SO',
            'ZAF' => 'ZA',
            'SGS' => 'GS',
            'KOR' => 'KR',
            'ESP' => 'ES',
            'LKA' => 'LK',
            'VCT' => 'VC',
            'SDN' => 'SD',
            'SUR' => 'SR',
            'SJM' => 'SJ',
            'SWZ' => 'SZ',
            'SWE' => 'SE',
            'CHE' => 'CH',
            'SYR' => 'SY',
            'STP' => 'ST',
            'TWN' => 'TW',
            'TJK' => 'TJ',
            'TZA' => 'TZ',
            'THA' => 'TH',
            'TLS' => 'TL',
            'TGO' => 'TG',
            'TKL' => 'TK',
            'TON' => 'TO',
            'TTO' => 'TT',
            'TUN' => 'TN',
            'TUR' => 'TR',
            'TKM' => 'TM',
            'TCA' => 'TC',
            'TUV' => 'TV',
            'UMI' => 'UM',
            'VIR' => 'VI',
            'UGA' => 'UG',
            'UKR' => 'UA',
            'ARE' => 'AE',
            'GBR' => 'GB',
            'USA' => 'US',
            'URY' => 'UY',
            'UZB' => 'UZ',
            'VUT' => 'VU',
            'GUY' => 'VA',
            'VEN' => 'VE',
            'VNM' => 'VN',
            'WLF' => 'WF',
            'ESH' => 'EH',
            'YEM' => 'YE',
            'ZMB' => 'ZM',
            'ZWE' => 'ZW',
            'ALA' => 'AX'
        );
        if ($iso3) {
            return array_key_exists($iso3, $iso2) ? $iso2[$iso3] : '';
        } else {
            return '';
        }
    }

    public static function generateHash($m_t_id, $amount, $cur_code, $trans_type)
    {
        return SHA1(
            Configuration::get('PAYLANE_GENERAL_HASH') . "|" .
            $m_t_id . "|" .
            $amount . "|" .
            $cur_code . "|" .
            $trans_type
        );
    }

}
