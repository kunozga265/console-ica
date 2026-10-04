<?php

namespace App\Http\Controllers\API\V1_2;

use App\Http\Controllers\Controller;
use App\Models\Cell;
use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function store(Request $request)
    {

        $request->validate([
            "cell_code" => "required",
            "amount" => "required",
            "description" => "required",
            "type" => "required",
        ]);

        $cell = Cell::where("code", $request->cell_code)->first();
        if(!$cell->verified){
            return response()->json(["message" => "Cell not verified. Please contact system administrator."], 400);
        }

        $current_balance = $cell->balance;

        if ($request->type == 0) {
            $new_balance = $current_balance + $request->amount;
        } else {
            $new_balance = $current_balance - $request->amount;
        }

        $cell->update([
            'balance' => $new_balance
        ]);

        $transaction = Transaction::create([
            "amount" => $request->amount,
            "type" => $request->type,
            "description" => $request->description,
            'cell_id' => $cell->id,
            'balance' => $new_balance
        ]);

        return response()->json(["message" => "Transaction recorded!"]);

    }
}
