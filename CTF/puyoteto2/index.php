<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>puyoteto Game</title>
    <style>
        body {
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #111;
        }

        canvas {
            border: 2px solid #fff;
            border-radius: 10px;
        }

        .game-info {
            position: absolute;
            top: 20px;
            left: 20px;
            color: white;
            font-family: Arial, sans-serif;
            font-size: 18px;
            background: rgba(0, 0, 0, 0.5);
            padding: 10px;
            border-radius: 5px;
        }

        .game-info span {
            display: block;
            font-size: 24px;
        }

        #monster {
            position: absolute;
            top: 50%;
            /* 縦方向中央 */
            right: 30px;
            /* 右側に配置 */
            transform: translateY(-50%);
            /* 縦方向中央に調整 */
            color: white;
            font-size: 24px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        #monster img {
            width: 300px;
            /* 画像の幅を大きくする */
            height: 300px;
            /* 画像の高さを大きくする */
            margin-bottom: 10px;
        }



        #monsterBar {
            width: 80px;
            height: 10px;
            background-color: #333;
            border-radius: 5px;
            overflow: hidden;
        }

        #monsterBar div {
            height: 100%;
            background-color: #00FF00;
            transition: width 0.2s ease-in-out;
        }

        #startButton {
            position: absolute;
            top: 50%;
            /* 縦方向中央 */
            left: 50%;
            /* 横方向中央 */
            transform: translate(-50%, -50%);
            /* ボタンの中央を基準に位置を調整 */
            padding: 10px 20px;
            font-size: 20px;
            color: white;
            background-color: #28a745;
            /* 緑色 */
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s, transform 0.2s;
        }

        #startButton:hover {
            background-color: #218838;
            /* ダークグリーン */
            transform: translate(-50%, -50%) scale(1.05);
            /* 少し拡大 */
        }

        #startButton:active {
            transform: translate(-50%, -50%) scale(0.95);
            /* 押下時に少し縮小 */
        }
    </style>

</head>

<body>
    <canvas id="gameCanvas" width="390" height="600"></canvas>
    <div id="gameInfo" class="game-info">
        <span id="score">Score: 0</span>
        <span id="time">Time: 0s</span>
    </div>
    <div id="monster">
        <img src="monster.png" alt="Monster">
        <div>HP: <span id="monsterHP">10000</span></div>
        <div id="monsterBar">
            <div></div>
        </div>
    </div>
    <button id="startButton">スタート</button>
    <script>
        document.getElementById('startButton').addEventListener('click', startGame);

        function startGame() {
            score = 0;
            monsterHP = 10000;
            grid = Array.from({
                length: ROWS
            }, () => Array(COLS).fill(0));
            piece = newPiece();
            startTime = Date.now();
            gameStarted = true;
            hideStartButton(); // スタートボタンを隠す
            gameLoop();
        }

        function hideStartButton() {
            document.getElementById('startButton').style.display = 'none';
        }

        function showStartButton() {
            document.getElementById('startButton').style.display = 'block';
        }


        const canvas = document.getElementById('gameCanvas');
        const ctx = canvas.getContext('2d');
        const BLOCK_SIZE = 30;
        const COLS = Math.floor(canvas.width / BLOCK_SIZE);
        const ROWS = Math.floor(canvas.height / BLOCK_SIZE);
        const COLORS = ['#000000', '#FF0000', '#00FF00', '#0000FF', '#FFFF00', '#FFA500', '#800080', '#00FFFF'];
        const TETROMINOS = [
            [
                [1, 1, 1, 1]
            ], // I
            [
                [1, 1],
                [1, 1]
            ], // O
            [
                [0, 1, 1],
                [1, 1, 0]
            ], // S
            [
                [1, 1, 0],
                [0, 1, 1]
            ], // Z
            [
                [1, 0, 0],
                [1, 1, 1]
            ], // L
            [
                [0, 0, 1],
                [1, 1, 1]
            ], // J
            [
                [0, 1, 0],
                [1, 1, 1]
            ], // T
        ];
        let grid = Array.from({
            length: ROWS
        }, () => Array(COLS).fill(0));
        let score = 0;
        let monsterHP = 10000;
        let startTime = Date.now();
        let piece, x, y;

        function newPiece() {
            const shape = TETROMINOS[Math.floor(Math.random() * TETROMINOS.length)];
            const color = Math.floor(Math.random() * (COLORS.length - 1)) + 1;
            return {
                shape,
                color,
                x: Math.floor(COLS / 2) - Math.floor(shape[0].length / 2),
                y: 0
            };
        }

        function drawGrid() {
            for (let row = 0; row < ROWS; row++) {
                for (let col = 0; col < COLS; col++) {
                    ctx.fillStyle = COLORS[grid[row][col]];
                    ctx.fillRect(col * BLOCK_SIZE, row * BLOCK_SIZE, BLOCK_SIZE, BLOCK_SIZE);

                    // 明るい色で枠線を描画
                    ctx.strokeStyle = "#444"; // グレーの線
                    ctx.lineWidth = 2; // 太さを少し太く
                    ctx.strokeRect(col * BLOCK_SIZE, row * BLOCK_SIZE, BLOCK_SIZE, BLOCK_SIZE);
                }
            }
        }


        function drawPiece(piece) {
            for (let row = 0; row < piece.shape.length; row++) {
                for (let col = 0; col < piece.shape[row].length; col++) {
                    if (piece.shape[row][col]) {
                        ctx.fillStyle = COLORS[piece.color];
                        ctx.fillRect((piece.x + col) * BLOCK_SIZE, (piece.y + row) * BLOCK_SIZE, BLOCK_SIZE, BLOCK_SIZE);
                    }
                }
            }
        }

        function checkCollision(piece) {
            for (let row = 0; row < piece.shape.length; row++) {
                for (let col = 0; col < piece.shape[row].length; col++) {
                    if (piece.shape[row][col]) {
                        const newX = piece.x + col;
                        const newY = piece.y + row;
                        if (newX < 0 || newX >= COLS || newY >= ROWS || grid[newY][newX] !== 0) {
                            return true;
                        }
                    }
                }
            }
            return false;
        }

        function lockPiece(piece) {
            for (let row = 0; row < piece.shape.length; row++) {
                for (let col = 0; col < piece.shape[row].length; col++) {
                    if (piece.shape[row][col]) {
                        grid[piece.y + row][piece.x + col] = piece.color;
                    }
                }
            }
            clearLines();
            if (score >= 100) {
                monsterHP -= 100;
            }
        }

        function clearLines() {
            let cleared;
            do {
                cleared = false;
                let toClear = new Set();

                // まずブロックを下に落とす
                for (let x = 0; x < COLS; x++) {
                    let emptyIndex = ROWS - 1; // 最下部からスタート
                    for (let y = ROWS - 1; y >= 0; y--) {
                        if (grid[y][x] !== 0) {
                            // 空でないセルを下に移動
                            grid[emptyIndex][x] = grid[y][x];
                            if (emptyIndex !== y) {
                                grid[y][x] = 0; // 元の位置を空にする
                            }
                            emptyIndex--;
                        }
                    }
                }

                // 横に5つ揃ったラインをチェック
                for (let y = 0; y < ROWS; y++) {
                    for (let x = 0; x < COLS - 4; x++) { // 5つ揃った場合に消えるように変更
                        if (grid[y][x] === grid[y][x + 1] && grid[y][x] === grid[y][x + 2] && grid[y][x] === grid[y][x + 3] && grid[y][x] === grid[y][x + 4] && grid[y][x] !== 0) {
                            let alreadyAdded = false;
                            for (let i = 0; i < 5; i++) {
                                if (toClear.has(`${y},${x + i}`)) {
                                    alreadyAdded = true;
                                    break;
                                }
                            }
                            if (!alreadyAdded) {
                                for (let i = 0; i < 5; i++) {
                                    toClear.add(`${y},${x + i}`);
                                }
                                cleared = true;
                                score += 100; // スコアを増加
                            }
                        }
                    }
                }

                // 縦に5つ揃ったラインをチェック
                for (let x = 0; x < COLS; x++) {
                    for (let y = 0; y < ROWS - 4; y++) {
                        if (grid[y][x] === grid[y + 1][x] && grid[y][x] === grid[y + 2][x] && grid[y][x] === grid[y + 3][x] && grid[y][x] === grid[y + 4][x] && grid[y][x] !== 0) {
                            let alreadyAdded = false;
                            for (let i = 0; i < 5; i++) {
                                if (toClear.has(`${y + i},${x}`)) {
                                    alreadyAdded = true;
                                    break;
                                }
                            }
                            if (!alreadyAdded) {
                                for (let i = 0; i < 5; i++) {
                                    toClear.add(`${y + i},${x}`);
                                }
                                cleared = true;
                                score += 100; // スコアを増加
                            }
                        }
                    }
                }

                // 消去するセルをグリッドから削除
                toClear.forEach(cell => {
                    const [y, x] = cell.split(',').map(Number);
                    grid[y][x] = 0; // セルを空にする
                });

            } while (cleared); // 再度消去があった場合は繰り返す

            // HPバーを更新
            drawMonster();
            drawScore(); // スコアを描画
        }





        function drawMonster() {
            const hpBarWidth = (monsterHP / 10000) * 80;
            document.getElementById('monsterHP').innerText = monsterHP;
            document.getElementById('monsterBar').children[0].style.width = `${hpBarWidth}px`;
        }

        function drawTime() {
            const elapsedTime = Math.floor((Date.now() - startTime) / 1000);
            document.getElementById('time').innerText = `Time: ${elapsedTime}s`;
        }

        function drawScore() {
            document.getElementById('score').innerText = `Score: ${score}`;
        }

        function gameLoop() {
            if (!gameStarted) return;

            ctx.clearRect(0, 0, canvas.width, canvas.height);
            drawGrid();
            drawPiece(piece);
            drawMonster();
            drawTime();
            drawScore();

            if (monsterHP <= 0) {
                fetch('flag.php')
                    .then(response => response.json())
                    .then(data => {
                        alert("You Win! " + data.flag);
                    })
                    .catch(error => console.error('Error:', error));
                return;
            }

            function lockPiece(piece) {
                for (let row = 0; row < piece.shape.length; row++) {
                    for (let col = 0; col < piece.shape[row].length; col++) {
                        if (piece.shape[row][col]) {
                            grid[piece.y + row][piece.x + col] = piece.color;
                        }
                    }
                }

                // HP減少用にスコアを一時保存
                const beforeScore = score;

                clearLines();

                const scoreGained = score - beforeScore;
                if (scoreGained > 0) {
                    monsterHP -= scoreGained; // 得点分だけHPを減らす
                }
            }



            if (Date.now() - startTime > 60000) {
                showStartButton(); // ゲームオーバー時にスタートボタンを表示
                alert("Game Over! Time's up!");
                return;
            }

            piece.y++;
            if (checkCollision(piece)) {
                piece.y--;
                lockPiece(piece);
                piece = newPiece();
                if (checkCollision(piece)) {
                    showStartButton(); // ゲームオーバー時にスタートボタンを表示
                    alert("Game Over! You lost!");
                    return;
                }
            }

            setTimeout(gameLoop, 500); // 落下速度を遅くした
        }

        function movePiece(event) {
            if (event.key === 'ArrowLeft' && !checkCollision({
                    ...piece,
                    x: piece.x - 1
                })) piece.x--;
            if (event.key === 'ArrowRight' && !checkCollision({
                    ...piece,
                    x: piece.x + 1
                })) piece.x++;
            if (event.key === 'ArrowDown' && !checkCollision({
                    ...piece,
                    y: piece.y + 1
                })) piece.y++;
            if (event.key === 'ArrowUp') {
                const rotated = piece.shape[0].map((_, index) => piece.shape.map(row => row[index])).reverse();
                if (!checkCollision({
                        ...piece,
                        shape: rotated
                    })) piece.shape = rotated;
            }
        }

        window.addEventListener('keydown', movePiece);

        piece = newPiece();
        gameLoop();
    </script>
</body>

</html>
