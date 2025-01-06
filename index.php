<?php
$successMessage = '';

if (isset($_POST['encode'])) {
    $image = $_FILES['image']['tmp_name'];
    $text = $_POST['text'];
    $successMessage = encodeImage($image, $text);
} elseif (isset($_POST['decode'])) {
    $image = $_FILES['image']['tmp_name'];
    $decodedMessage = decodeImage($image);
}

function textToBinary($text) {
    $bin = '';
    for ($i = 0; $i < strlen($text); $i++) {
        $bin .= str_pad(decbin(ord($text[$i])), 8, '0', STR_PAD_LEFT);
    }
    return $bin;
}

function encodeImage($image, $text) {
    $img = imagecreatefrompng($image);
    $width = imagesx($img);
    $height = imagesy($img);
    $binText = textToBinary($text) . '1111111111111110'; // End marker
    $index = 0;

    for ($y = 0; $y < $height; $y++) {
        for ($x = 0; $x < $width; $x++) {
            if ($index >= strlen($binText)) break 2;
            $rgb = imagecolorat($img, $x, $y);
            $colors = imagecolorsforindex($img, $rgb);
            $colors['red'] = ($colors['red'] & 0xFE) | (int)$binText[$index];
            $index++;
            $newColor = imagecolorallocate($img, $colors['red'], $colors['green'], $colors['blue']);
            imagesetpixel($img, $x, $y, $newColor);
        }
    }
    imagepng($img, 'encoded.png');
    imagedestroy($img);
    return 'Image encoded successfully.';
}

function decodeImage($image) {
    $img = imagecreatefrompng($image);
    $width = imagesx($img);
    $height = imagesy($img);
    $binText = '';

    for ($y = 0; $y < $height; $y++) {
        for ($x = 0; $x < $width; $x++) {
            $rgb = imagecolorat($img, $x, $y);
            $red = ($rgb >> 16) & 0xFF;
            $binText .= $red & 1;
        }
    }

    // Find position of the end marker
    $pos = strpos($binText, '1111111111111110');
    if ($pos !== false) {
        $binText = substr($binText, 0, $pos);  // Trim up to the marker
    }

    $chunks = str_split($binText, 8);
    $text = '';
    foreach ($chunks as $chunk) {
        $text .= chr(bindec($chunk));
    }
    
    imagedestroy($img);
    return $text;
}

$decodedMessage = '';
$scrollToMessage = false;
$scrollToSuccessMessage = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['decode'])) {
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $decodedMessage = decodeImage($_FILES['image']['tmp_name']);
            $scrollToMessage = true;
        }
    } elseif (isset($_POST['encode']) && $successMessage) {
        $scrollToSuccessMessage = true;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Steganography Web App</title>
    <link rel="stylesheet" type="text/css" href="style.css">
    <script>
        function scrollToMessage() {
            const messageDiv = document.getElementById('decoded-message');
            if (messageDiv) {
                messageDiv.scrollIntoView({ behavior: 'smooth' });
            }
        }

        function scrollToSuccessMessage() {
            const successMessageDiv = document.getElementById('success-message');
            if (successMessageDiv) {
                successMessageDiv.scrollIntoView({ behavior: 'smooth' });
            }
        }
    </script>
</head>
<body>
   
    <div class="main-container">
        <h1>Image Steganography</h1>

        <form method="post" enctype="multipart/form-data" id="steganography-form">
            <label>Upload Image (PNG only):</label>
            <input type="file" name="image" required>
            <br>
            <label>Text to Hide:</label>
            <textarea name="text"></textarea>
            <br>
            <button type="submit" name="encode">Encode Text</button>
            <button type="submit" name="decode">Decode Text</button>
        </form>

        <?php if ($successMessage): ?>
            <div id="success-message" class="success-message">
                <h2><?php echo htmlspecialchars($successMessage); ?></h2>
            </div>
            <script>
                scrollToSuccessMessage();
            </script>
        <?php endif; ?>

        <?php if ($decodedMessage): ?>
            <div id="decoded-message" class="decoded-message">
                <h2>Hidden Message:</h2>
                <p><?php echo htmlspecialchars($decodedMessage); ?></p>
            </div>
            <script>
                scrollToMessage();
            </script>
        <?php endif; ?>
    </div>
</body>
</html>
