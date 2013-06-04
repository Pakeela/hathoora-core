<?php
namespace hathoora;

/**
 * Utility of common functions
 */
class utils
{
    /**
     * Returns the value of token
     *
     * @param string $token that we want to get the value for ex: {{name}}
     * @param array $arrTokens that has a key of token ex: 'name' = 'xyz'
     */
    public static function deTokenize($token, &$arrTokens)
    {
        $value = $token;
        if (is_array($arrTokens) && preg_match_all('/\{\{(.+?)\}\}/', $token, $arrMatch))
        {
            foreach ($arrMatch[1] as $token)
            {
                if (isset($arrTokens[$token]))
                    $value = str_replace('{{'. $token .'}}', $arrTokens[$token], $value);
            }
        }
        else if (isset($arrTokens[$value]))
            $value =& $arrTokens[$value];
            
        return $value;    
    }
}