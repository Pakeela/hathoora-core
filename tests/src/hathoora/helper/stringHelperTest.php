<?php
namespace hathoora\test\helper
{
    use hathoora\helper\stringHelper;

    class stringHelperTest extends \PHPUnit_Framework_TestCase
    {

        public function testSlugify()
        {
            $arrStrings = array(
                array(
                    'string' => 'hello world',
                    'expected' => 'hello-world'
                ),
                array(
                    'string' => 'HELLO WORLD UPPERCASE',
                    'expected' => 'hello-world-uppercase'
                ),
                array(
                    'string' => ' hello world trimmed ',
                    'expected' => 'hello-world-trimmed'
                ),
                array(
                    'string' => ' hello world *&^%()+ unwated &^$#@@~`characters-12_3',
                    'expected' => 'hello-world-unwated-characters-12-3'
                ),
                array(
                    'string' => 'French CHars Ë-À-Ì-Â-Í-Ã-Î-Ä-Ï-Ç-Ò-È-Ó-É-Ô-Ê-Õ-Ö-ê-Ù-ë-Ú-î-Û-ï-Ü-ô-Ý-õ-â-û-ã-ÿ-ç',
                    'expected' => 'french-chars-e-a-i-a-i-a-i-a-i-c-o-e-o-e-o-e-o-o-e-u-e-u-i-u-i-u-o-y-o-a-u-a-y-c'
                ),
                array(
                    // http://us2.php.net/manual/en/function.iconv.php#105507
                    'string' => 'Weiß, Goldmann, Göbel, Weiss, Göthe, Goethe und Götz',
                    'expected' => 'weiss-goldmann-gobel-weiss-gothe-goethe-und-gotz'
                ),
                array(
                    // http://us2.php.net/manual/en/function.iconv.php#113925
                    'string' => 'señor café 0123 කොහොමද ශ්‍රී ලංකා  hello Žluťoučký kůň ÀÁÂ,ÃÄÅ,Æ,ÇÈ,ÉÊË,ÌÍÎ,ÏÐÑ,ÒÓÔ,ÕÖØ,ÙÚÛ,ÜÝ,Þ,ß,àáâ,ãäå,æ,çèé,êëì,íîï,ðñò,óôõ,öøù,úûýý,þ,ÿŔŕYA(亚） HE（何） Tra Mỹ',
                    'expected' => 'senor-cafe-0123--------hello-zlutoucky-kun-aaa-aaa-ae-ce-eee-iii-in-ooo-oo-uuu-uy--ss-aaa-aaa-ae-cee-eei-iii-no-ooo-ou-uuyy--yrrya--he--tra-my'
                )
            );

            foreach($arrStrings as $arrTest)
            {
                if (isset($arrTest['locale']))
                    setlocale(LC_ALL, $arrTest['locale']);
                else
                    setlocale(LC_ALL, 'en_US');

                $this->assertEquals($arrTest['expected'], stringHelper::slugify($arrTest['string']));
            }
        }

        public function testObfuscation()
        {
            $message = 'This is a test message señor café 0123';
            $key = '87197198171^%141';
            $obfuscatedMessage = stringHelper::obfuscate($message, $key);
            $deobfuscatedMessage = stringHelper::deObfuscate($obfuscatedMessage, $key);

            $this->assertEquals($message, $deobfuscatedMessage);
            $this->assertNotEquals($message, $obfuscatedMessage);
            $this->assertNotEquals($obfuscatedMessage, $deobfuscatedMessage);
        }

    }
}