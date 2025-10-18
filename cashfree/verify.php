<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title> Gateway | Status Check</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            margin: 0;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(135deg, #4a1e9e, #1f074f);
        }

        .success-container {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            width: 90%;
            max-width: 400px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .success-icon {
            width: 80px;
            height: 80px;
            background: #dbc355;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0 auto 20px;
            color: white;
            font-size: 40px;
        }

        .failed-icon {
            width: 80px;
            height: 80px;
            background: #ad0808;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0 auto 20px;
            color: white;
            font-size: 40px;
        }

        .success-title {
            color: #2e7d32;
            font-size: 24px;
            margin-bottom: 10px;
        }

        .failed-title {
            color: #ad0808;
            font-size: 24px;
            margin-bottom: 10px;
        }

        .transaction-id {
            color: #666;
            margin-bottom: 20px;
        }

        .home-button {
            padding: 12px 24px;
            background: #1f074f;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }

        .bg-success {
            background: #4CAF50;
        }

        .d-none {
            display: none;
        }

        .loader {
            border: 4px solid #fff;
            border-top: 4px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }
    </style>
</head>
<body>
    <div class="success-container d-none" id="success-container">
        <div class="success-icon" id="success-icon">
            <div class="loader" id="loader"></div>
            <span id="checkmark" class="d-none"></span>
        </div>
        <h1 class="success-title" id="success-title"></h1>
        <p class="transaction-id">Transaction ID: <span id="txn-id"></span></p>
        <a href="/cashfree" class="home-button">Back to Home</a>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
    const successContainer = document.getElementById('success-container');
    const successIcon = document.getElementById('success-icon');
    const successTitle = document.getElementById('success-title');
    const txnid = document.getElementById('txn-id');
    const loader = document.getElementById('loader');
    const checkmark = document.getElementById('checkmark');

    const urlParams = new URLSearchParams(window.location.search);
    const txnIdParam = urlParams.get('txnId') || 'N/A';
    txnid.textContent = txnIdParam;
    txnid.classList.add('d-none');

    successContainer.classList.remove('d-none');

    fetch("verifypayment.php", {
        method: "POST",
        body: JSON.stringify({
                    txnid: txnid.textContent
                })
    })
    .then(response => response.json())
    .then(data => {
        debugger;
        setTimeout(function () {
            loader.style.display = 'none';
            checkmark.classList.remove('d-none');
            txnid.textContent = data.order_id || txnIdParam;
            txnid.classList.remove('d-none');
            successContainer.classList.remove('d-none');

            if (data.wpResponse.state === 'COMPLETED') {
                successTitle.innerText = "Payment Successful!";
                successTitle.classList.remove('failed-title');
                successTitle.classList.add('success-title');
                successIcon.innerText = '✓';
                successIcon.classList.remove('failed-icon');
                successIcon.classList.add('success-icon', 'bg-success');
            } else {
                successTitle.innerText = data.order_status || "Payment Failed!";
                successTitle.classList.add('failed-title');
                successTitle.classList.remove('success-title');
                successIcon.innerText = 'X';
                successIcon.classList.add('failed-icon');
                successIcon.classList.remove('success-icon', 'bg-success');
            }
        }, 300);
    })
    .catch(error => {
        console.error("Error:", error);
        loader.style.display = 'none';
        successTitle.innerText = "Something went wrong!";
    });
});
    </script>
</body>
</html>