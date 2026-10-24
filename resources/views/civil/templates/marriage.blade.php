<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Acte de Mariage {{ $act->registryLabel() }} — {{ config('mairie.name') }}</title>
    <style>
        @page { size: A4 landscape; margin: 4mm; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 8.5pt;
            line-height: 1.15;
            color: #000;
            background: #fff;
            margin: 0;
            padding: 0;
        }
        .page {
            width: 289mm;
            height: 202mm;
            display: flex;
            box-sizing: border-box;
            page-break-after: always;
        }
        .page:last-child { page-break-after: avoid; }
        .col {
            flex: 1 1 33.333%;
            padding: 4mm 6mm;
            box-sizing: border-box;
            border-right: 1px dashed #666;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .col:last-child { border-right: none; }
        
        .ar {
            font-family: 'Traditional Arabic', 'Amiri', 'Noto Naskh Arabic', Arial, sans-serif;
            direction: rtl;
            unicode-bidi: embed;
            font-size: 9.5pt;
        }
        .hdr { text-align: center; margin-bottom: 2mm; }
        .hdr h2 { margin: 0; font-size: 10.5pt; font-weight: bold; text-transform: uppercase; }
        .hdr .sub { font-size: 7.5pt; color: #333; font-style: italic; }
        .act-title { display: flex; justify-content: space-between; border-bottom: 1.5px solid #000; padding-bottom: 1mm; margin-bottom: 2mm; font-weight: bold; font-size: 9pt; }
        
        .row { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 1.2mm; min-height: 4.2mm; }
        .row .lbl { white-space: nowrap; font-size: 8pt; color: #111; }
        .row .val { flex: 1; border-bottom: 1px solid #000; margin: 0 1.5mm; font-weight: bold; font-size: 8.5pt; padding: 0 1mm; min-height: 3.5mm; }
        
        .section-break { border-top: 1px dotted #888; margin: 2mm 0; }
        .sig-block { display: flex; justify-content: space-between; margin-top: 2mm; padding-top: 1mm; font-size: 8pt; }
        .btn-print {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #0a2347;
            color: #fff;
            padding: 10px 18px;
            font-size: 13px;
            font-weight: bold;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
            z-index: 100;
        }
        @media print { .btn-print { display: none; } }
    </style>
</head>
<body>

<button class="btn-print" onclick="window.print()">Imprimer l'Acte de Mariage (Recto / Verso)</button>

@php
    $volets = [
        ['title' => 'Souche', 'ar' => 'أصلي', 'sub' => ''],
        ['title' => 'Volet N° 2', 'ar' => 'ورقة رقم 2', 'sub' => '(à remettre aux conjoints) / تسلم إلى الزوج'],
        ['title' => 'Volet N° 1', 'ar' => 'ورقة رقم 1', 'sub' => '(à remettre à la conjointe) / تسلم إلى الزوجة'],
    ];
@endphp

<!-- RECTO : PAGE 1 (SOUCHE, VOLET 2, VOLET 1) -->
<div class="page">
    @foreach($volets as $v)
    <div class="col">
        <div>
            <div class="hdr">
                <div><span style="font-weight:bold;">{{ $v['title'] }}</span> &nbsp; <span class="ar">{{ $v['ar'] }}</span></div>
                @if($v['sub'])<div class="sub">{{ $v['sub'] }}</div>@endif
                <div style="font-size:7.5pt; margin-top:1mm;">Sous-Préfecture de Moundou / محافظة</div>
            </div>

            <div class="act-title">
                <span>ACTE DE MARIAGE N° <u>{{ $act->registry_number ? sprintf('%04d', $act->registry_number) : '______' }}</u></span>
                <span class="ar">عقد زواج رقم</span>
            </div>

            <div class="row">
                <span class="lbl">Centre d'Etat civil de :</span>
                <span class="val">{{ $act->centre->name }}</span>
                <span class="ar">مركز الأحوال المدنية</span>
            </div>
            <div class="row">
                <span class="lbl">Le (en lettres) :</span>
                <span class="val">{{ $act->celebration_date_words }}</span>
                <span class="ar">بتاريخ</span>
            </div>
            <div class="row">
                <span class="lbl">Par devant Nous :</span>
                <span class="val">{{ $act->officer_name ?? ($act->validator?->name ?? 'Officier d’état civil') }}</span>
                <span class="ar">أمامنا</span>
            </div>
            <div class="row">
                <span class="lbl">ont comparu en la Commune de :</span>
                <span class="val">{{ $act->celebration_place }}</span>
                <span class="ar">حضر علنا في دار البلدية</span>
            </div>

            <!-- Époux -->
            <div class="row" style="margin-top:2mm;">
                <span class="lbl"><b>M.</b></span>
                <span class="val" style="font-size:9.5pt;">{{ $act->husband_first_names }} {{ $act->husband_last_name }}</span>
                <span class="ar">السيد</span>
            </div>
            <div class="row">
                <span class="lbl">Né le / à :</span>
                <span class="val">{{ $act->husband_birth_date }} à {{ $act->husband_birth_place }}</span>
                <span class="ar">ولد بتاريخ / في</span>
            </div>
            <div class="row">
                <span class="lbl">Canton / Sous-Préf. :</span>
                <span class="val">{{ $act->husband_canton }} / {{ $act->husband_sub_prefecture }}</span>
                <span class="ar">مقاطعة / مركز</span>
            </div>
            <div class="row">
                <span class="lbl">Fils de :</span>
                <span class="val">{{ $act->husband_father }}</span>
                <span class="ar">ابن</span>
            </div>
            <div class="row">
                <span class="lbl">et de :</span>
                <span class="val">{{ $act->husband_mother }}</span>
                <span class="ar">وابن</span>
            </div>
            <div class="row">
                <span class="lbl">Situation antérieure :</span>
                <span class="val">{{ ucfirst($act->husband_marital_status) }} @if($act->husband_previous_spouse)de {{ $act->husband_previous_spouse }}@endif</span>
                <span class="ar">أرمل أو مطلق</span>
            </div>
            <div class="row">
                <span class="lbl">Acte de naissance N° :</span>
                <span class="val">{{ $act->husband_birth_act_ref }} @if($act->husband_birth_act_date)du {{ $act->husband_birth_act_date }}@endif</span>
                <span class="ar">شهادة ميلاد رقم</span>
            </div>

            <!-- Épouse -->
            <div class="row" style="margin-top:2mm;">
                <span class="lbl"><b>et Mme</b></span>
                <span class="val" style="font-size:9.5pt;">{{ $act->wife_first_names }} {{ $act->wife_last_name }}</span>
                <span class="ar">والسيدة</span>
            </div>
            <div class="row">
                <span class="lbl">Née le / à :</span>
                <span class="val">{{ $act->wife_birth_date }} à {{ $act->wife_birth_place }}</span>
                <span class="ar">مولودة بتاريخ / في</span>
            </div>
            <div class="row">
                <span class="lbl">Canton / Sous-Préf. :</span>
                <span class="val">{{ $act->wife_canton }} / {{ $act->wife_sub_prefecture }}</span>
                <span class="ar">مقاطعة / مركز</span>
            </div>
            <div class="row">
                <span class="lbl">Fille de :</span>
                <span class="val">{{ $act->wife_father }}</span>
                <span class="ar">ابنة</span>
            </div>
            <div class="row">
                <span class="lbl">et de :</span>
                <span class="val">{{ $act->wife_mother }}</span>
                <span class="ar">وابنة</span>
            </div>
            <div class="row">
                <span class="lbl">Situation antérieure :</span>
                <span class="val">{{ ucfirst($act->wife_marital_status) }} @if($act->wife_previous_spouse)de {{ $act->wife_previous_spouse }}@endif</span>
                <span class="ar">أرملة أو مطلقة</span>
            </div>
            <div class="row">
                <span class="lbl">Acte de naissance N° :</span>
                <span class="val">{{ $act->wife_birth_act_ref }} @if($act->wife_birth_act_date)du {{ $act->wife_birth_act_date }}@endif</span>
                <span class="ar">شهادة ميلاد رقم</span>
            </div>

            <div style="font-size:7.5pt; font-style:italic; margin-top:1.5mm; text-align:center;">
                qui nous ont déclaré devant témoins avoir contracté volontairement mariage.<br>
                <span class="ar">اللذان أعلنا أمام الشهود عقد زواج طوعي</span>
            </div>

            <div class="row" style="margin-top:1mm;">
                <span class="lbl">Témoin époux :</span>
                <span class="val">{{ $act->husband_witness }} ({{ $act->husband_witness_residence }})</span>
            </div>
            <div class="row">
                <span class="lbl">Témoin épouse :</span>
                <span class="val">{{ $act->wife_witness }} ({{ $act->wife_witness_residence }})</span>
            </div>
        </div>

        <div class="sig-block">
            <div>Signatures des époux et témoins</div>
            <div style="text-align:right;">
                <div>L'Officier de l'Etat Civil</div>
                <div class="ar">توقيع ضابط الأحوال المدنية</div>
            </div>
        </div>
    </div>
    @endforeach
</div>

<!-- VERSO : PAGE 2 (STIPULATIONS PARTICULIÈRES & CONTRAT DE MARIAGE) -->
<div class="page">
    @foreach($volets as $v)
    <div class="col">
        <div>
            <div class="hdr">
                <h2 style="font-size:9.5pt;">STIPULATIONS PARTICULIÈRES</h2>
                <div class="ar">شروط خاصة</div>
            </div>

            <div style="font-weight:bold; font-size:8.5pt; margin-top:3mm; margin-bottom:1.5mm; border-bottom:1px solid #000;">
                Stipulations relatives à la dot : / <span class="ar">معلومات متعلقة بالمهر</span>
            </div>

            <div class="row">
                <span class="lbl">Montant en espèces :</span>
                <span class="val">{{ $act->dowry_cash > 0 ? \App\Support\Money::fcfa($act->dowry_cash) : 'Néant' }}</span>
                <span class="ar">مبلغ نقدي</span>
            </div>
            <div class="row">
                <span class="lbl">Montant en nature :</span>
                <span class="val">{{ $act->dowry_nature ?? 'Néant' }}</span>
                <span class="ar">مبلغ عيني</span>
            </div>
            <div class="row">
                <span class="lbl">Dates des versements :</span>
                <span class="val">{{ $act->dowry_payment_dates ?? 'Comptant à la célébration' }}</span>
                <span class="ar">تاريخ الدفع</span>
            </div>

            <div style="font-weight:bold; font-size:8.5pt; margin-top:6mm; margin-bottom:1.5mm; border-bottom:1px solid #000;">
                Contrat de mariage : / <span class="ar">عقد الزواج</span>
            </div>
            <div style="font-size:8pt; font-style:italic; margin-bottom:2mm;">
                Sur notre interpellation, les futurs époux ont déclaré :<br>
                <span class="ar">على استجوابنا رد الزوجين بتحديد</span>
            </div>

            @if($act->has_marriage_contract)
                <div style="padding:2mm; border:1px solid #000; font-size:8pt; margin-bottom:2mm;">
                    <b>[X]</b> Qu'un contrat de mariage a été reçu le <b>{{ $act->contract_date }}</b><br>
                    par Maître <b>{{ $act->contract_notary }}</b>, Notaire.<br>
                    <span class="ar">لقد عقدا عقد زواج مسبق لدى الموثق</span>
                </div>
                <div style="font-size:8pt; color:#666; text-decoration:line-through;">
                    [ ] Qu'il n'a pas été fait de contrat de mariage.
                </div>
            @else
                <div style="padding:2mm; border:1px solid #000; font-size:8.5pt; margin-bottom:2mm;">
                    <b>[X] Qu'il n'a pas été fait de contrat de mariage.</b><br>
                    <span class="ar">لم يعقدا زواجا من قبل بموجب عقد خاص</span>
                </div>
                <div style="font-size:8pt; color:#666; text-decoration:line-through;">
                    [ ] Qu'un contrat de mariage a été reçu par notaire.
                </div>
            @endif
        </div>

        <div class="sig-block">
            <div style="font-size:7pt; color:#666;">Code de vérification : {{ $act->verification_code }}</div>
            <div style="text-align:right;">
                <div>L'Officier d'Etat-Civil</div>
                <div class="ar">توقيع ضابط الأحوال المدنية</div>
            </div>
        </div>
    </div>
    @endforeach
</div>

</body>
</html>
