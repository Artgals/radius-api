<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RadiusController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'profile' => 'required|string|max:30',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 999,
                'message' => 'failed',
                'input' => $request->all(),
                'error' => [
                    'code' => 101,
                    'message' => 'There were problems with your input'
                ],
            ]);
        }

        $profile = DB::table('profiles')
            ->select('id' , 'name')
            ->where('name', $request->profile)->first();

        if (!$profile) {
            return response()->json([
                'status' => 999,
                'message' => 'failed',
                'input' => $request->all(),
                'error' => [
                    'code' => 102,
                    'message' => 'The profile provided is invalid.'
                ],
            ]);
        }

        $shuffle1 = str_shuffle(Str::random(5));
        $shuffle2 = str_shuffle(Str::random(5));
        $shuffle3 = str_shuffle(Str::random(5));
        $shuffle4 = str_shuffle(Str::random(5));
        $shuffle5 = str_shuffle(Str::random(5));

        $voucher = strtoupper(substr(str_shuffle($shuffle1.$shuffle2.$shuffle3.$shuffle4.$shuffle5),0,6));

        $exist = DB::table('radcheck')->where('username', $voucher)->count();

        if ($exist) {
            return response()->json([
                'status' => 999,
                'message' => 'failed',
                'input' => $request->all(),
                'error' => [
                    'code' => 103,
                    'message' => 'Failed to create voucher code.'
                ],
            ]);
        }

        DB::table('radcheck')->insert([
            'user_id' => $request->user()->id,
            'username' => $voucher,
            'attribute' => 'ClearText-Password',
            'op' => ':=',
            'value' => $voucher,
            'target' => 'hotspot',
            'source' => 'sms',
            'profile_id' => $profile->id,
            'created_at' => Carbon::now()->toDateTimeString(),
            'updated_at' => Carbon::now()->toDateTimeString(),
        ]);

        DB::table('radusergroup')->insert([
            'username' => $voucher,
            'groupname' => $profile->name,
            'priority' => 1,
        ]);

        DB::table('usages')->insert([
            'username' => $voucher,
        ]);
        
        return response()->json([
            'status' => 0,
            'message' => 'success',
            'input' => $request->all(),
            'voucher' => $voucher
        ]);
    }
}
