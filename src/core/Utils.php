<?php

namespace MapasCulturais;

class Utils {
    static function removeAccents($string) {
        if (!preg_match('/[\x80-\xff]/', $string))
            return $string;

        $chars = array(
            // Decompositions for Latin-1 Supplement
            chr(195) . chr(128) => 'A', chr(195) . chr(129) => 'A',
            chr(195) . chr(130) => 'A', chr(195) . chr(131) => 'A',
            chr(195) . chr(132) => 'A', chr(195) . chr(133) => 'A',
            chr(195) . chr(135) => 'C', chr(195) . chr(136) => 'E',
            chr(195) . chr(137) => 'E', chr(195) . chr(138) => 'E',
            chr(195) . chr(139) => 'E', chr(195) . chr(140) => 'I',
            chr(195) . chr(141) => 'I', chr(195) . chr(142) => 'I',
            chr(195) . chr(143) => 'I', chr(195) . chr(145) => 'N',
            chr(195) . chr(146) => 'O', chr(195) . chr(147) => 'O',
            chr(195) . chr(148) => 'O', chr(195) . chr(149) => 'O',
            chr(195) . chr(150) => 'O', chr(195) . chr(153) => 'U',
            chr(195) . chr(154) => 'U', chr(195) . chr(155) => 'U',
            chr(195) . chr(156) => 'U', chr(195) . chr(157) => 'Y',
            chr(195) . chr(159) => 's', chr(195) . chr(160) => 'a',
            chr(195) . chr(161) => 'a', chr(195) . chr(162) => 'a',
            chr(195) . chr(163) => 'a', chr(195) . chr(164) => 'a',
            chr(195) . chr(165) => 'a', chr(195) . chr(167) => 'c',
            chr(195) . chr(168) => 'e', chr(195) . chr(169) => 'e',
            chr(195) . chr(170) => 'e', chr(195) . chr(171) => 'e',
            chr(195) . chr(172) => 'i', chr(195) . chr(173) => 'i',
            chr(195) . chr(174) => 'i', chr(195) . chr(175) => 'i',
            chr(195) . chr(177) => 'n', chr(195) . chr(178) => 'o',
            chr(195) . chr(179) => 'o', chr(195) . chr(180) => 'o',
            chr(195) . chr(181) => 'o', chr(195) . chr(182) => 'o',
            chr(195) . chr(182) => 'o', chr(195) . chr(185) => 'u',
            chr(195) . chr(186) => 'u', chr(195) . chr(187) => 'u',
            chr(195) . chr(188) => 'u', chr(195) . chr(189) => 'y',
            chr(195) . chr(191) => 'y',
            // Decompositions for Latin Extended-A
            chr(196) . chr(128) => 'A', chr(196) . chr(129) => 'a',
            chr(196) . chr(130) => 'A', chr(196) . chr(131) => 'a',
            chr(196) . chr(132) => 'A', chr(196) . chr(133) => 'a',
            chr(196) . chr(134) => 'C', chr(196) . chr(135) => 'c',
            chr(196) . chr(136) => 'C', chr(196) . chr(137) => 'c',
            chr(196) . chr(138) => 'C', chr(196) . chr(139) => 'c',
            chr(196) . chr(140) => 'C', chr(196) . chr(141) => 'c',
            chr(196) . chr(142) => 'D', chr(196) . chr(143) => 'd',
            chr(196) . chr(144) => 'D', chr(196) . chr(145) => 'd',
            chr(196) . chr(146) => 'E', chr(196) . chr(147) => 'e',
            chr(196) . chr(148) => 'E', chr(196) . chr(149) => 'e',
            chr(196) . chr(150) => 'E', chr(196) . chr(151) => 'e',
            chr(196) . chr(152) => 'E', chr(196) . chr(153) => 'e',
            chr(196) . chr(154) => 'E', chr(196) . chr(155) => 'e',
            chr(196) . chr(156) => 'G', chr(196) . chr(157) => 'g',
            chr(196) . chr(158) => 'G', chr(196) . chr(159) => 'g',
            chr(196) . chr(160) => 'G', chr(196) . chr(161) => 'g',
            chr(196) . chr(162) => 'G', chr(196) . chr(163) => 'g',
            chr(196) . chr(164) => 'H', chr(196) . chr(165) => 'h',
            chr(196) . chr(166) => 'H', chr(196) . chr(167) => 'h',
            chr(196) . chr(168) => 'I', chr(196) . chr(169) => 'i',
            chr(196) . chr(170) => 'I', chr(196) . chr(171) => 'i',
            chr(196) . chr(172) => 'I', chr(196) . chr(173) => 'i',
            chr(196) . chr(174) => 'I', chr(196) . chr(175) => 'i',
            chr(196) . chr(176) => 'I', chr(196) . chr(177) => 'i',
            chr(196) . chr(178) => 'IJ', chr(196) . chr(179) => 'ij',
            chr(196) . chr(180) => 'J', chr(196) . chr(181) => 'j',
            chr(196) . chr(182) => 'K', chr(196) . chr(183) => 'k',
            chr(196) . chr(184) => 'k', chr(196) . chr(185) => 'L',
            chr(196) . chr(186) => 'l', chr(196) . chr(187) => 'L',
            chr(196) . chr(188) => 'l', chr(196) . chr(189) => 'L',
            chr(196) . chr(190) => 'l', chr(196) . chr(191) => 'L',
            chr(197) . chr(128) => 'l', chr(197) . chr(129) => 'L',
            chr(197) . chr(130) => 'l', chr(197) . chr(131) => 'N',
            chr(197) . chr(132) => 'n', chr(197) . chr(133) => 'N',
            chr(197) . chr(134) => 'n', chr(197) . chr(135) => 'N',
            chr(197) . chr(136) => 'n', chr(197) . chr(137) => 'N',
            chr(197) . chr(138) => 'n', chr(197) . chr(139) => 'N',
            chr(197) . chr(140) => 'O', chr(197) . chr(141) => 'o',
            chr(197) . chr(142) => 'O', chr(197) . chr(143) => 'o',
            chr(197) . chr(144) => 'O', chr(197) . chr(145) => 'o',
            chr(197) . chr(146) => 'OE', chr(197) . chr(147) => 'oe',
            chr(197) . chr(148) => 'R', chr(197) . chr(149) => 'r',
            chr(197) . chr(150) => 'R', chr(197) . chr(151) => 'r',
            chr(197) . chr(152) => 'R', chr(197) . chr(153) => 'r',
            chr(197) . chr(154) => 'S', chr(197) . chr(155) => 's',
            chr(197) . chr(156) => 'S', chr(197) . chr(157) => 's',
            chr(197) . chr(158) => 'S', chr(197) . chr(159) => 's',
            chr(197) . chr(160) => 'S', chr(197) . chr(161) => 's',
            chr(197) . chr(162) => 'T', chr(197) . chr(163) => 't',
            chr(197) . chr(164) => 'T', chr(197) . chr(165) => 't',
            chr(197) . chr(166) => 'T', chr(197) . chr(167) => 't',
            chr(197) . chr(168) => 'U', chr(197) . chr(169) => 'u',
            chr(197) . chr(170) => 'U', chr(197) . chr(171) => 'u',
            chr(197) . chr(172) => 'U', chr(197) . chr(173) => 'u',
            chr(197) . chr(174) => 'U', chr(197) . chr(175) => 'u',
            chr(197) . chr(176) => 'U', chr(197) . chr(177) => 'u',
            chr(197) . chr(178) => 'U', chr(197) . chr(179) => 'u',
            chr(197) . chr(180) => 'W', chr(197) . chr(181) => 'w',
            chr(197) . chr(182) => 'Y', chr(197) . chr(183) => 'y',
            chr(197) . chr(184) => 'Y', chr(197) . chr(185) => 'Z',
            chr(197) . chr(186) => 'z', chr(197) . chr(187) => 'Z',
            chr(197) . chr(188) => 'z', chr(197) . chr(189) => 'Z',
            chr(197) . chr(190) => 'z', chr(197) . chr(191) => 's'
        );

        $string = strtr($string, $chars);

        return $string;
    }

    static function slugify($text, string $divider = '-')
    {
        if (empty($text)) {
            return '';
        }

        $text = self::removeAccents($text);

        // replace non letter or digits by divider
        $text = preg_replace('~[^\pL\d]+~u', $divider, $text);

        // transliterate
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

        // remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);

        // trim
        $text = trim($text, $divider);

        // remove duplicate divider
        $text = preg_replace('~-+~', $divider, $text);

        // lowercase
        $text = strtolower($text);

        return $text;
    }

    static function isTheSameName($name1, $name2) {
        if(self::slugify($name1) == self::slugify($name2)) {
            return true;
        }
        $len = max(strlen($name1), strlen($name2));
        if ($len < 10) {
            $cutoff = 95;
        } else if ($len < 15) {
            $cutoff = 90;
        } else if ($len < 20) {
            $cutoff = 85;
        } else if ($len >= 20) {
            $cutoff = 80;
        }

        similar_text(self::slugify($name1), self::slugify($name2), $similarity);
        
        if($similarity >= $cutoff) {
            return true;
        }

        return false;
    }

    static function formatCnpjCpf($value) {
      $CPF_LENGTH = 11;
      $cnpj_cpf = preg_replace("/\D/", '', $value ?: '');
      
      if (strlen($cnpj_cpf) === $CPF_LENGTH) {
        return preg_replace("/(\d{3})(\d{3})(\d{3})(\d{2})/", "\$1.\$2.\$3-\$4", $cnpj_cpf);
      } 
      
      return preg_replace("/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/", "\$1.\$2.\$3/\$4-\$5", $cnpj_cpf);
    }

    /**
     * Retorna o usuário da rede social 
     *  
     * @param string $domain Dominio da rede social.
     * @param string $value
     *  
     * @return string|null 
     */
    static function parseSocialMediaUser(string $domain, ?string $value) : string|null {
        $result = null;

        if($value){

            $domain = preg_quote($domain);
            
            $_value = trim($value);
            $_value = preg_replace("~^(?:https?:\/\/)?(?:www\.)?~i", "", $_value);
            $_value = rtrim($_value, '/');
    
            if (preg_match("~(?:{$domain}/(?:profile\.php\?id=)?((@|channel/)?[-\w\d.]+))~i", $_value, $matches)) {
                $result = $matches[1];
            }
            if (preg_match("~{$domain}/in/([-_\w\d]+)~i", $_value, $matches)) {
                $result = $matches[1];
            }
            if (preg_match("~^open\.spotify\.com/user/([-_\w\d]+)~i", $_value, $matches)) {
                $result = $matches[1];
            }
            else if(preg_match("/^((@|channel\/)?[-\w\d\.]+)$/i", $_value, $matches)){
                $result = $matches[1];
            }
        }
        return $result;
    }

    /**
     * Converte uma string contendo valores separados por nova linha em um array.
     *
     * Esta função é útil para converter entradas de texto em uma lista de valores, como
     * uma lista de tags ou categorias.
     *
     * @param string $string A string contendo os valores separados por nova linha.
     * @return array Um array contendo os valores únicos e trimados da string de entrada.
     */
    static function nl2array(string $string): array
    {
        $values = preg_split('/\r\n|\r|\n/', $string);
        $values = array_map('trim', $values);
        $values = array_filter($values);
        $values = array_unique($values);
        return $values;
    }

    /**
     * Detecta o formato de uma data
     * @param string $dateString 
     * @return string|bool 
     */
    static function detectDateFormat(string $dateString): string | bool
    {
        $patterns = [
            'd/m/Y' => '/^\d{1,2}\/\d{1,2}\/\d{4}$/', // dd/mm/yyyy
            'm/d/Y' => '/^\d{1,2}\/\d{2}\/\d{4}$/', // mm/dd/yyyy
            'Y-m-d' => '/^\d{4}-\d{1,2}-\d{1,2}$/',    // yyyy-mm-dd
            'd-m-Y' => '/^\d{1,2}-\d{1,2}-\d{4}$/',    // dd-mm-yyyy
            'm-d-Y' => '/^\d{1,2}-\d{1,2}-\d{4}$/',    // mm-dd-yyyy
            'Y/m/d' => '/^\d{4}\/\d{1,2}\/\d{1,2}$/'    // yyyy/mm/dd
        ];

        foreach ($patterns as $format => $pattern) {
            if (preg_match($pattern, $dateString)) {
                return $format;
            }
        }

        return false;
    }

    /**
     * Sanitiza uma string (ou array de strings), removendo acentos, ajustando o case e limpando espaços.
     *
     * - Se o input for um array, aplica recursivamente nos valores.
     * - Remove acentos e espaços em excesso.
     * - Converte para letras minúsculas ou maiúsculas conforme o parâmetro $case.
     *
     * @param string|array $input A string ou array de strings a serem normalizadas.
     * @param string $case Define o case final da string. Pode ser 'lower' (padrão) ou 'upper'.
     *
     * @return string|array A string (ou array) sanitizada.
     */
    public static function sanitizeString(string|array $input, string $case = 'lower', bool $removeSpecials = false): string|array
    {
        if (is_array($input)) {
            $result = [];
            foreach ($input as $key => $value) {
                $result[$key] = self::sanitizeString($value, $case, $removeSpecials);
            }
            return $result;
        }

        $input = mb_convert_encoding((string)$input, 'UTF-8', mb_detect_encoding($input));

        $input = self::removeAccents($input);

        if ($removeSpecials) {
            $input = preg_replace('/[^\p{L}\p{N}\s]/u', '', $input); // remove tudo que não for letra, número ou espaço
        }

        $input = trim($input);

        return $case === 'upper' ? mb_strtoupper($input, 'UTF-8') : mb_strtolower($input, 'UTF-8');
    }


}
