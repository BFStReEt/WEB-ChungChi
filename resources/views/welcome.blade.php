<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test reCAPTCHA</title>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>

<body>
    <h1>Test captcha ?</h1>
    <form id="test-form">
        <div class="g-recaptcha" data-sitekey="6LcIW7oqAAAAAMg3xI8xH0vwJ5oqM-DDO6hytOQr"></div>
        <button type="submit">Submit</button>
    </form>

    <script>
        document.getElementById('test-form').addEventListener('submit', function(event) {
            event.preventDefault();
            const token = grecaptcha.getResponse();
            if (!token) {
                alert('Vui lòng hoàn thành recaptcha !!!');
                return;
            }
            console.log('reCAPTCHA:', token);
        });
    </script>
</body>

</html>