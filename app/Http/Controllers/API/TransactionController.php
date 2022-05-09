<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Midtrans\Config;
use Midtrans\Snap;

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

    // fungsi untuk update transaksi ketika berhasil atau gagal 
    // hanya sebagai testing saja  
    // sebenarnya kurang aman dan direkomendasikan untuk di-comment saja
    public function update(Request $request, $id){
        $transaction = Transaction::findOrFail($id);

        $transaction->update($request->all()); // meng-update semua yang ada di-request
        
        return ResponseFormatter::success($transaction, 'Transaction data successfully updated');
    }

    public function checkout(Request $request){

        // validasi request dari frontend
        $request->validate([
            'food_id' => 'required|exists:food,id',
            'user_id' => 'required|exists:users,id',
            'quantity' => 'required',
            'total' => 'required',
            'status' => 'required',
        ]);

        $transaction = Transaction::create([
            'food_id' => $request->food_id,
            'user_id' => $request->user_id,
            'quantity' => $request->quantity,
            'total' => $request->total,
            'status' => $request->status,
            'payment_url' => '', // akan di-update nanti setelah memanggil midtrans
        ]);

        // konfigurasi MidTrans supaya bisa dipakai
        Config::$serverKey = config('services.midtrans.serverKey');
        Config::$isProduction = config('services.midtrans.isProduction');
        Config::$isSanitized = config('services.midtrans.isSanitized');
        Config::$is3ds = config('services.midtrans.is3ds');
        
        // panggil transaksi yang tadi dibuat, namun kali ini spesifik dengan relasinya
        $transaction = Transaction::with(['food', 'user'])->find($transaction->id);

        // Membuat Transaksi Midtrans
        $midtrans = [
            'transaction_details' => [
                'order_id' => $transaction->id,
                'gross_amount' => (int) $transaction->total,
            ],
            'customer_details' => [
                'first_name' => $transaction->user->name,
                'email' => $transaction->user->email,
            ],
            'enable_payments' => ['gopay', 'bank_transfer'],
            'vtweb' => []
        ];

        // Memanggil Midtrans
        try {
            // ambil halaman payment midtrans
            $paymentUrl = Snap::createTransaction($midtrans)->redirect_url;
            
            // update data di db
            $transaction->payment_url = $paymentUrl;
            $transaction->save();

            // Mengembalikan data ke API 
            return ResponseFormatter::success($transaction, 'Transaction Success');

        } catch (Exception $e) {
            return ResponseFormatter::error($e->getMessage(), 'Transaction Failed');
        }
    }
}
