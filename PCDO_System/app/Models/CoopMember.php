<?php

namespace App\Models;

use App\Traits\SyncLogger;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoopMember extends Model
{
    /** @use HasFactory<\Database\Factories\CoopMemberFactory> */
    use HasFactory, SyncLogger;

    protected $fillable = [
        'coop_id',
        'position',
        'contact',
        'email',
        'first_name',
        'middle_name',
        'last_name',
        'marital_status',
        'street',
        'city',
        'telephone',
        'birthdate',
        'age',
        'sex',
        'citizenship',
        'birthplace',
        'height',
        'weight',
        'religion',
        'spouse_name',
        'spouse_occupation',
        'spouse_age',
        'father_name',
        'father_occupation',
        'father_age',
        'mother_name',
        'mother_occupation',
        'mother_age',
        'parent_address',
        'emergency_name',
        'emergency_contact',
        'dependent1_name',
        'dependent1_relationship',
        'dependent1_birthdate',
        'dependent1_age',
        'dependent2_name',
        'dependent2_relationship',
        'dependent2_birthdate',
        'dependent2_age',
        'elementary_start',
        'elementary_end',
        'elementary_name',
        'elementary_degree',
        'hs_start',
        'hs_end',
        'hs_name',
        'hs_degree',
        'college_start',
        'college_end',
        'college_name',
        'college_degree',
        'course_start',
        'course_end',
        'course_name',
        'course_degree',
        'others_start',
        'others_end',
        'others_name',
        'others_degree',
        'company1_start',
        'company1_end',
        'company1_name',
        'company1_position',
        'company1_rfl',
        'company2_start',
        'company2_end',
        'company2_name',
        'company2_position',
        'company2_rfl',
        'company3_start',
        'company3_end',
        'company3_name',
        'company3_position',
        'company3_rfl',
        'company4_start',
        'company4_end',
        'company4_name',
        'company4_position',
        'company4_rfl',
        'company5_start',
        'company5_end',
        'company5_name',
        'company5_position',
        'company5_rfl',
        'ref1_name',
        'ref1_company',
        'ref1_position',
        'ref1_contact',
        'ref2_name',
        'ref2_company',
        'ref2_position',
        'ref2_contact',
        'is_representative',
        'active_year',
    ];

    public function cooperatives()
    {
        return $this->belongsToOne(Cooperative::class, 'coop_member_cooperative', 'coop_member_id', 'cooperative_id');
    }

    public function files()
    {
        return $this->hasMany(CoopMemberFile::class, 'coop_member_id');
    }
}
