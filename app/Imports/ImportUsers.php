<?php

namespace App\Imports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Hash;

class ImportUsers implements ToModel, WithHeadingRow
{
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        //delete
        if ($row['imported'] == '0' || $row['imported'] == 'FALSE') {
            User::where('id', $row['id'])->delete();
            return null;
        }

        //update
        $exist = User::where('id', $row['id'])->first();
        if ($exist) {
            $exist->update([
                'name' => $row['name'],
                'email' => $row['email'],
                'password' => Hash::make($row['password']),
                'imported' => $row['imported']
            ]);
            return null;
        }

        //create
        return new User([
            'name'     => $row['name'],
            'email'    => $row['email'],
            'password' => Hash::make($row['password']),
            'imported' => $row['imported']
        ]);
    }
}
