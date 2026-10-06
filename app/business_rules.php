<?php
declare(strict_types=1);
function asay_po_statuses(): array {return ['Hazırlanıyor','Üretimde','Sevkiyata Hazır','Yolda','Kısmi Teslimat','Teslim Edildi'];}
function asay_po_transitions(): array {return [
 'Hazırlanıyor'=>['Hazırlanıyor','Üretimde','Sevkiyata Hazır'],
 'Üretimde'=>['Üretimde','Hazırlanıyor','Sevkiyata Hazır'],
 'Sevkiyata Hazır'=>['Sevkiyata Hazır','Üretimde','Yolda','Kısmi Teslimat'],
 'Yolda'=>['Yolda','Sevkiyata Hazır','Kısmi Teslimat','Teslim Edildi'],
 'Kısmi Teslimat'=>['Kısmi Teslimat','Yolda','Teslim Edildi'],
 'Teslim Edildi'=>['Teslim Edildi'],
];}
function asay_po_transition_allowed(string $from,string $to): bool {return in_array($to,asay_po_transitions()[$from]??[$from],true);}
function asay_po_progress(string $status,float $fallback=0): float {return ['Hazırlanıyor'=>0,'Üretimde'=>35,'Sevkiyata Hazır'=>90,'Yolda'=>95,'Kısmi Teslimat'=>97,'Teslim Edildi'=>100][$status]??$fallback;}
