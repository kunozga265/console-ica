<?php

namespace App\Http\Controllers\API\V2;

use App\Http\Controllers\Controller;
use App\Http\Resources\V2\TransactionResource;
use App\Models\Cell;
use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            "cellCode" => ["required", "string"],
            "amount" => ["required", "numeric"],
            "description" => ["required", "string"],
            "type" => ["required", "integer", "in:0,1"],
        ]);

        $cell = Cell::where("code", $validated["cellCode"])->first();
        if (!$cell) {
            return response()->json(["message" => "Cell not found"], 404);
        }
        if (!$cell->verified) {
            return response()->json(["message" => "Cell not verified. Please contact system administrator."], 400);
        }

        $newBalance = $validated["type"] == 0
            ? $cell->balance + $validated["amount"]
            : $cell->balance - $validated["amount"];

        $cell->update(["balance" => $newBalance]);

        $transaction = Transaction::create([
            "amount" => $validated["amount"],
            "type" => $validated["type"],
            "description" => $validated["description"],
            "cell_id" => $cell->id,
            "balance" => $newBalance,
        ]);

        return new TransactionResource($transaction);
    }
}
