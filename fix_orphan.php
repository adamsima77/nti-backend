<?php
use Modules\Evaluation\Models\CommissionMember;
use Modules\Evaluation\Models\Evaluation;

$commissionIds = CommissionMember::where('call_id', 4)->pluck('commission_id')->unique();
$validMemberIds = CommissionMember::whereIn('commission_id', $commissionIds)
    ->where(function ($q) { $q->whereNull('call_id')->orWhere('call_id', 4); })
    ->pluck('id');

$deleted = Evaluation::where('application_id', 2)
    ->whereNotIn('commission_member_id', $validMemberIds)
    ->delete();

echo "Zmazaných orphan hodnotení: {$deleted}\n";
echo "Platní členovia: " . $validMemberIds->implode(', ') . "\n";
