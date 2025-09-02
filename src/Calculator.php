<?php

namespace App;

// 0除算など、計算中のエラーを表現するためのカスタム例外クラス
class DivisionByZeroException extends \Exception {}

class Calculator
{
    /**
     * 2つの数値と演算子を受け取り、計算結果を返すメソッド
     *
     * @param float $num1 最初の数値
     * @param float $num2 2番目の数値
     * @param string $operator 演算子 (+, -, *, /)
     * @return float 計算結果
     * @throws DivisionByZeroException 0で除算しようとした場合にスローされる
     * @throws \InvalidArgumentException 無効な演算子が渡された場合にスローされる
     */
    public function calculate(float $num1, float $num2, string $operator): float
    {
        switch ($operator) {
            case '+':
                return $num1 + $num2;
            case '-':
                return $num1 - $num2;
            case '*':
                return $num1 * $num2;
            case '/':
                if ($num2 == 0) {
                    // 0での除算はエラーとして例外をスローする
                    throw new DivisionByZeroException("Cannot divide by zero.");
                }
                return $num1 / $num2;
            default:
                // 対応していない演算子の場合はエラー
                throw new \InvalidArgumentException("Invalid operator provided.");
        }
    }
}