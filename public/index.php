<?php
require_once __DIR__ . '/../src/Calculator.php';

use App\Calculator;
use App\DivisionByZeroException;

$result = '';
$error = '';

// POSTリクエストがない場合（初回アクセス時など）は、結果表示エリアは空にする
// 計算が実行された結果を表示するため、ここでは計算処理はPOST時のみとする
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $num1_str = $_POST['num1'] ?? '';
    $num2_str = $_POST['num2'] ?? '';
    $operator = $_POST['operator'] ?? '';

    // JavaScriptから渡される値が数値として有効か確認
    if (is_numeric($num1_str) && is_numeric($num2_str) && !empty($operator)) {
        $num1 = (float)$num1_str;
        $num2 = (float)$num2_str;

        $calculator = new Calculator();
        try {
            $result = $calculator->calculate($num1, $num2, $operator);
        } catch (DivisionByZeroException $e) {
            $error = 'エラー: 0で割ることはできません。';
        } catch (InvalidArgumentException $e) {
            $error = 'エラー: 無効な演算子です。';
        } catch (Exception $e) {
            $error = '予期せぬエラーが発生しました。';
        }
    } else {
        // JavaScript側でバリデーションされるが、念のためサーバー側でもチェック
        $error = 'エラー: 不正な入力です。';
    }
}
?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ミニマル電卓</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

    <div class="calculator">
        <div class="display">
            <input type="text" id="display"
                value="<?= htmlspecialchars($error ? $error : $result, ENT_QUOTES, 'UTF-8') ?>" readonly>
        </div>

        <div class="buttons-grid">
            <button class="button number" data-value="7">7</button>
            <button class="button number" data-value="8">8</button>
            <button class="button number" data-value="9">9</button>
            <button class="button operator" data-value="/">÷</button>

            <button class="button number" data-value="4">4</button>
            <button class="button number" data-value="5">5</button>
            <button class="button number" data-value="6">6</button>
            <button class="button operator" data-value="*">×</button>

            <button class="button number" data-value="1">1</button>
            <button class="button number" data-value="2">2</button>
            <button class="button number" data-value="3">3</button>
            <button class="button operator" data-value="-">-</button>

            <button class="button number" data-value="0">0</button>
            <button class="button number" data-value=".">.</button>
            <button class="button clear">C</button>
            <button class="button operator" data-value="+">+</button>

            <button class="button equals">=</button>
        </div>
    </div>

    <script>
    // JavaScriptによる電卓ロジック
    const display = document.getElementById('display');
    let currentInput = ''; // 現在入力されている数値
    let prevInput = ''; // 前の数値
    let operator = null; // 選択された演算子
    let waitingForNewInput = false; // 演算子選択後、次の数値入力を待っている状態か

    document.querySelectorAll('.button.number').forEach(button => {
        button.addEventListener('click', () => {
            if (waitingForNewInput) {
                currentInput = button.dataset.value;
                waitingForNewInput = false;
            } else {
                // 小数点の場合、既に小数点が含まれていなければ追加
                if (button.dataset.value === '.' && currentInput.includes('.')) {
                    return;
                }
                currentInput += button.dataset.value;
            }
            display.value = currentInput;
        });
    });

    document.querySelectorAll('.button.operator').forEach(button => {
        button.addEventListener('click', () => {
            if (currentInput === '' && prevInput === '') return; // 何も入力されていない場合は何もしない

            if (prevInput && operator && currentInput && !waitingForNewInput) {
                // 連続して演算子が押された場合、前回の計算を実行
                performCalculation();
            }

            prevInput = currentInput === '' ? (prevInput === '' ? '0' : prevInput) :
                currentInput; // currentInputが空の場合、前の結果を使用
            operator = button.dataset.value;
            waitingForNewInput = true;
            display.value = prevInput + ' ' + button.textContent + ' '; // 演算子も表示
            currentInput = ''; // 次の入力のためにリセット
        });
    });

    document.querySelector('.button.clear').addEventListener('click', () => {
        currentInput = '';
        prevInput = '';
        operator = null;
        waitingForNewInput = false;
        display.value = '';
        // PHP側でエラーが出た場合のリセット
        if (display.value.startsWith('エラー')) {
            display.value = '';
        }
    });

    document.querySelector('.button.equals').addEventListener('click', () => {
        if (prevInput === '' || currentInput === '' || operator === null) {
            return; // 計算に必要な要素が揃っていない場合は何もしない
        }
        performCalculation();
        operator = null; // 計算後は演算子をリセット
        waitingForNewInput = true; // 次の入力を待つ
    });

    function performCalculation() {
        // PHPに計算を依頼するためのフォームを動的に作成
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'index.php';
        form.style.display = 'none'; // ユーザーには見せない

        const num1Input = document.createElement('input');
        num1Input.type = 'hidden';
        num1Input.name = 'num1';
        num1Input.value = prevInput;
        form.appendChild(num1Input);

        const num2Input = document.createElement('input');
        num2Input.type = 'hidden';
        num2Input.name = 'num2';
        num2Input.value = currentInput;
        form.appendChild(num2Input);

        const operatorInput = document.createElement('input');
        operatorInput.type = 'hidden';
        operatorInput.name = 'operator';
        operatorInput.value = operator;
        form.appendChild(operatorInput);

        document.body.appendChild(form);
        form.submit(); // フォームを送信してPHPで計算を実行
    }

    // ページロード時にPHPからの計算結果があれば表示
    window.onload = () => {
        const initialDisplayValue = display.value;
        if (initialDisplayValue && !initialDisplayValue.startsWith('エラー')) {
            currentInput = initialDisplayValue; // PHPからの計算結果を現在の入力として設定
            prevInput = ''; // 演算子選択前の数値はリセット
            operator = null; // 演算子もリセット
            waitingForNewInput = true; // 次の入力を待つ
        } else if (initialDisplayValue.startsWith('エラー')) {
            // エラー表示の場合は、計算状態をリセット
            currentInput = '';
            prevInput = '';
            operator = null;
            waitingForNewInput = false;
        }
    };
    </script>
</body>

</html>