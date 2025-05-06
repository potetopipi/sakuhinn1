<?php
session_start();

echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reveal Flag</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        .secret {
            display: none; 
        }
        .secret.visible {
            display: block; 
        }
    </style>
</head>
<body>

<h1>Find the flag</h1>
<div id="flagContainer"></div>
<button id="revealButton" class="secret">Flag</button>

<p>CSSを書き換えてflagをゲットしよう！</p>
<p>ヒント -隠れているボタンを出現させてみよう-</p>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const flagContainer = document.getElementById("flagContainer");
        const revealButton = document.getElementById("revealButton");

        // ボタンが表示されているか確認するための関数
        function checkVisibility() {
            const style = window.getComputedStyle(revealButton);
            return style.display !== "none";
        }

        // ボタンをクリックしたときの処理
        revealButton.addEventListener("click", function() {
            fetch("flag.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: "reveal=true"
            })
            .then(response => response.text())
            .then(data => {
                flagContainer.textContent = data;
            });
        });

        // CSSが変更されたかをチェック
        const observer = new MutationObserver(() => {
            if (checkVisibility()) {
                observer.disconnect(); // 一度だけチェックするためにオブザーバーを停止
                revealButton.classList.add("visible");
            }
        });

        // ボタンのスタイルが変更されるのを監視する
        observer.observe(revealButton, { attributes: true, attributeFilter: ["style"] });
    });
</script>

</body>
</html>';
?>

