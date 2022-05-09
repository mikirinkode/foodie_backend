<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    //  satu fungsi ini menghandle dari semua request
    public function all(Request $request){
        
        // filtering request
        $id = $request->input('id');
        $limit = $request->input('limit', 6); // default data yang terpanggil 6 menu, tpi bisa dirubah ckup dipanggil aja
        
        // untuk mengambil data transaksi berdasarkan makanan tertentu
        // atau berdasarkan status
        $food_id = $request->input('food_id');
        $status = $request->input('status');

        // filter khusus id
        if ($id){
            // transaksi yang berelasi dengan objek food dan user tertentu
            $transaction = Transaction::with(['food', 'user'])->find($id);
            if ($transaction){
                return ResponseFormatter::success(
                    $transaction,
                    'Data retrived successfully'
                );
            } else {
                return ResponseFormatter::error(
                    null,
                    'Data not found',
                    404
                );
            }
        }
        
        $transaction = Transaction::with(['food', 'user'])->where('user_id', Auth::user()->id);  // where (dimana) data transaksi yang diambil hanya data user yang sedang login

        if ($food_id){
             $transaction->where('food_id', $food_id);
        }

        if ($status){
             $transaction->where('status', $status);
        }

        return ResponseFormatter::success(
            $transaction->paginate($limit),
            'List data of Transactions retrived successfully'
        );
    }
}
