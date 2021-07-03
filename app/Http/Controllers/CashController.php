<?php

namespace App\Http\Controllers;

use App\Cash;
use App\Denomination;
use App\Move;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CashController extends Controller
{


    public function loadCash()
    {
        $this->load(request()->all());
        return response('load success');
    }

    //Vaciar Caja
    public function emptyCash()
    {
        $cash = Cash::all();
        $this->empty($cash);

        return response('empty success');
    }

    //Pagos
    public function pay()
    {
        $example = request()->all();

        //validar que lo los valores de currency sean >= que value (valor a pagar)
        $sum = 0;
        foreach ($example['currencies'] as  $currency) {
            $sum += $currency['quantity'] * $currency['value'];
        }

        if ($sum < $example['value']) {
            return response('Te falta dinero ' . ($example['value'] - $sum));
        }

        //validamos que tengamos que dar vueltos
        $totalDevolver = $sum - $example['value'];

        if ($totalDevolver > 0) {

            $cash = DB::table('cash')->join('denominations', 'denominations.id', '=', 'cash.denomination_id')
                ->where('cash.quantity', '>=', 1)
                ->where('denominations.value', '<=', $totalDevolver)
                ->orderBy('denominations.value', 'DESC')
                ->select('denominations.value', 'cash.quantity')
                ->get();

            $m =  $this->payRecursivo($cash, $totalDevolver, []);
            $sum = $this->addChange($m);

            if(count($cash)){
                
                if ($sum < $totalDevolver) {
    
                    return response('No hay para devolver $' . ($totalDevolver - $sum) . ' de vuelto');
    
                } else {
    
                    //registrar la salida o entrega del cambio.
                    $this->returnChange($m);
    
                    //Registrar pago
                    $this->payRegister($example);
                }
            } else {

                return response('No hay dinero en la caja');
            }

        }

        return response()->json(['vuelto' => $totalDevolver, 'denominaciones' => $m]);
    }


    //Estado general de la caja
    public function cashState()
    {
        $response = [];
        $cash = Cash::with(['denomination', 'denomination.currency'])->get();
        foreach ($cash as $value) {
            $response[] =  array(
                'quantity' => $value->quantity,
                'value' => $value->denomination->value,
                'denomination' => $value->denomination->currency->name
            );
        }

        return response()->json($response);
    }

    //Log de eventos o movimientos
    public function moves()
    {
        $moves = Move::with(['denomination', 'denomination.currency'])->get();
        return response()->json($moves);
    }

    public function cashStateByDate(Request $request)
    {

        $request->validate([
            'start_date' => 'required|date_format:Y-m-d H:i:s',
            'end_date' => 'required|date_format:Y-m-d H:i:s',
        ]);

        $starDate = $request->start_date;
        $endDate = $request->end_date;

        $moves = DB::table('moves')->join('denominations', 'denominations.id', 'moves.denomination_id')
        ->join('cash', 'cash.denomination_id', '=', 'denominations.id')
        ->select(
            'moves.move',
            DB::raw("SUM(denominations.value) as amount"),
        )
        ->whereBetween('moves.created_at', [$starDate, $endDate])
        ->groupBy('moves.move')
        ->get();

        return response($moves);

    }


    //Método recursivo para optimizar la entrega del cambio.
    function payRecursivo($cash, $totalReturn, $change)
    {
        $currency = null;
        foreach ($cash as $key =>  $value) {
            if ($value->quantity > 0 && $value->value  <= $totalReturn) {
                $currency = $value;
                $value->quantity -= 1;
                $cash[$key] = $value;
                break;
            }
        }

        if (!$currency || $totalReturn <= 0) {
            return $change;
        }

        if (isset($change[$currency->value])) {
            $change[$currency->value] += 1;
        } else {
            $change[$currency->value] = 1;
        }

        return $this->payRecursivo($cash, ($totalReturn - $currency->value), $change);
    }

    //Método para cargar caja.
    public function load($data)
    {
        foreach ($data as $value) {
            $exist = Denomination::where('value', $value['value'])->exists();

            if ($exist) {
                $demomination = Denomination::where('value', $value['value'])->first();

                for ($i = 0; $i < $value['quantity']; $i++) {
                    Move::create([
                        'move' => 'E',
                        'denomination_id' => $demomination->id
                    ]);
                }

                $cash = Cash::where('denomination_id', $demomination->id)->first();

                if (!$cash) {
                    Cash::create([
                        'quantity' => $value['quantity'],
                        'denomination_id' => $demomination->id
                    ]);
                } else {
                    $cash->quantity += $value['quantity'];
                    $cash->save();
                }
            }
        }
    }

    //Método para registrar pago
    public function payRegister($data)
    {
        foreach ($data['currencies'] as $value) {
            $exist = Denomination::where('value', $value['value'])->exists();

            if ($exist) {
                $demomination = Denomination::where('value', $value['value'])->first();

                for ($i = 0; $i < $value['quantity']; $i++) {
                    Move::create([
                        'move' => 'E',
                        'denomination_id' => $demomination->id
                    ]);
                }

                $cash = Cash::where('denomination_id', $demomination->id)->first();

                if (!$cash) {
                    Cash::create([
                        'quantity' => $value['quantity'],
                        'denomination_id' => $demomination->id
                    ]);
                } else {
                    $cash->quantity += $value['quantity'];
                    $cash->save();
                }
            }
        }
    }

    //Método para vaciar la caja
    public function empty($cash)
    {
        foreach ($cash as $value) {

            for ($i = 0; $i < $value->quantity; $i++) {
                Move::create([
                    'move' => 'S',
                    'denomination_id' => $value->denomination_id
                ]);
            }

            $value->quantity = 0;
            $value->save();
        }
    }

    //Método para sumar denomiaciones al momento de entregar el cambio
    public function addChange($demominations)
    {
        $add = 0;
        foreach ($demominations as $key => $value) {
            $add += $key * $value;
        }
        return $add;
    }

    public function returnChange($demominations)
    {
        foreach ($demominations as $value => $quantity) {

            for ($i = 0; $i < $quantity; $i++) {

                $demomination = Denomination::where('value', $value)->first();
                Move::create([
                    'move' => 'S',
                    'denomination_id' => $demomination->id
                ]);
            }
        }
    }
}
