<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Acte de Décès {{ $act->registryLabel() }} — {{ config('mairie.name') }}</title>
    <style>
        @page { size: A4 landscape; margin: 4mm; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 8pt;
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
        }
        .col {
            flex: 1 1 25%;
            padding: 3mm 4mm;
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
            font-size: 9pt;
        }
        .hdr { text-align: center; margin-bottom: 1.5mm; }
        .hdr h2 { margin: 0; font-size: 9.5pt; font-weight: bold; text-transform: uppercase; }
        .hdr .sub { font-size: 7pt; color: #333; font-style: italic; }
        .act-title { display: flex; justify-content: space-between; border-bottom: 1px solid #000; padding-bottom: 0.8mm; margin-bottom: 1.5mm; font-weight: bold; font-size: 8.5pt; }
        
        .row { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 1mm; min-height: 3.8mm; }
        .row .lbl { white-space: nowrap; font-size: 7.5pt; color: #111; }
        .row .val { flex: 1; border-bottom: 1px solid #000; margin: 0 1mm; font-weight: bold; font-size: 8pt; padding: 0 1mm; min-height: 3mm; }
        
        .sig-block { display: flex; justify-content: space-between; margin-top: 2mm; padding-top: 1mm; font-size: 7.5pt; }
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

<button class="btn-print" onclick="window.print()">Imprimer l'Acte de Décès (Format Officiel 4 Volets)</button>

@php
    $volets = [
        ['title' => 'Souche', 'ar' => 'أصلي', 'sub' => ''],
        ['title' => 'Volet N° 1', 'ar' => 'ورقة رقم 1', 'sub' => '(A utiliser pour fichier alphabétique) / ترسل للمركز الرئيسي'],
        ['title' => 'Volet N° 2', 'ar' => 'ورقة ثانية', 'sub' => '(A remettre au déclarant) / يسلم إلى المصرح'],
    ];
@endphp

<div class="page">
    @foreach($volets as $v)
    <div class="col">
        <div>
            <div class="hdr">
                <div style="font-size:7pt; font-weight:bold;">RÉPUBLIQUE DU TCHAD / <span class="ar">جمهورية تشاد</span></div>
                <div style="font-size:7pt;">Département du Lac Wey · Sous-Préfecture de Moundou</div>
                <div style="margin-top:1mm;"><span style="font-weight:bold;">{{ $v['title'] }}</span> &nbsp; <span class="ar">{{ $v['ar'] }}</span></div>
                @if($v['sub'])<div class="sub">{{ $v['sub'] }}</div>@endif
            </div>

            <div class="act-title">
                <span>ACTE DE DÉCÈS N° <u>{{ $act->registry_number ? sprintf('%04d', $act->registry_number) : '______' }}</u></span>
                <span class="ar">شهادة وفاة رقم</span>
            </div>

            <div class="row">
                <span class="lbl">Centre d'Etat-Civil de :</span>
                <span class="val">{{ $act->centre->name }}</span>
                <span class="ar">مركز الأحوال المدنية</span>
            </div>
            <div class="row">
                <span class="lbl">Le (date déclaration) :</span>
                <span class="val">{{ $act->declaration_date->format('d/m/Y') }}</span>
                <span class="ar">بتاريخ</span>
            </div>
            <div class="row">
                <span class="lbl">S'est présenté(e) M. :</span>
                <span class="val">{{ $act->declarant_name }}</span>
                <span class="ar">حضر (ة)</span>
            </div>
            <div class="row">
                <span class="lbl">Domicilié(e) à :</span>
                <span class="val">{{ $act->declarant_residence }}</span>
                <span class="ar">مقيم في</span>
            </div>
            <div class="row">
                <span class="lbl">Qualité / Lien :</span>
                <span class="val">{{ $act->declarant_quality ?? 'Parent / Déclarant' }}</span>
                <span class="ar">صفة المصرح</span>
            </div>

            <div style="font-size:7pt; font-style:italic; margin:1mm 0; border-top:1px dashed #aaa; pt:1mm;">
                qui a déclaré le décès survenu à / <span class="ar">أعلن عن وفاة حصلت في</span>
            </div>

            <div class="row">
                <span class="lbl">Lieu du décès :</span>
                <span class="val">{{ $act->death_place }}</span>
                <span class="ar">مكان الوفاة</span>
            </div>
            <div class="row">
                <span class="lbl">Le (en toutes lettres) :</span>
                <span class="val">{{ $act->death_date_words }} @if($act->death_time)à {{ $act->death_time }}@endif</span>
                <span class="ar">بتاريخ</span>
            </div>

            <div class="row" style="margin-top:1.5mm;">
                <span class="lbl"><b>De (Défunt) :</b></span>
                <span class="val" style="font-size:9pt;">{{ $act->deceased_first_names }} {{ $act->deceased_last_name }}</span>
                <span class="ar">المتوفى</span>
            </div>
            <div class="row">
                <span class="lbl">Sexe :</span>
                <span class="val">{{ $act->sex === 'M' ? 'Masculin' : 'Féminin' }}</span>
                <span class="ar">الجنس</span>
            </div>
            <div class="row">
                <span class="lbl">Situation :</span>
                <span class="val">{{ \App\Models\DeathAct::MARITAL_STATUSES[$act->marital_status] ?? $act->marital_status }} @if($act->spouse_name)de {{ $act->spouse_name }}@endif</span>
                <span class="ar">عازب / متزوج / أرمل</span>
            </div>
            <div class="row">
                <span class="lbl">Profession :</span>
                <span class="val">{{ $act->deceased_profession ?? 'Sans profession' }}</span>
                <span class="ar">المهنة</span>
            </div>
            <div class="row">
                <span class="lbl">Né(e) à / le :</span>
                <span class="val">{{ $act->deceased_birth_place }} ({{ $act->deceased_birth_date }})</span>
                <span class="ar">ولد في / بتاريخ</span>
            </div>
            <div class="row">
                <span class="lbl">Fils/Fille de :</span>
                <span class="val">{{ $act->father_name ?? '—' }}</span>
                <span class="ar">ابن</span>
            </div>
            <div class="row">
                <span class="lbl">et de :</span>
                <span class="val">{{ $act->mother_name ?? '—' }}</span>
                <span class="ar">وابن</span>
            </div>
            <div class="row">
                <span class="lbl">Réf. acte de naissance :</span>
                <span class="val">{{ $act->birth_act_ref ?? 'Non fournie' }}</span>
                <span class="ar">شهادة ميلاد رقم</span>
            </div>
            @if($act->burial_place)
            <div class="row">
                <span class="lbl">Lieu d'inhumation :</span>
                <span class="val">{{ $act->burial_place }}</span>
                <span class="ar">مكان الدفن</span>
            </div>
            @endif
        </div>

        <div class="sig-block">
            <div>
                <div>Le Déclarant</div>
                <div class="ar">المصرح</div>
            </div>
            <div style="text-align:right;">
                <div>L'Officier d'Etat-Civil</div>
                <div class="ar">ضابط الأحوال المدنية</div>
            </div>
        </div>
    </div>
    @endforeach

    <!-- 4ÈME COLONNE : AVIS DE MENTION DE DÉCÈS -->
    <div class="col" style="background:#fcfcfc;">
        <div>
            <div class="hdr">
                <div style="font-size:7pt; font-weight:bold;">RÉPUBLIQUE DU TCHAD</div>
                <div style="font-size:7pt;">Département du Lac Wey / Moundou</div>
                <h2 style="font-size:9pt; margin-top:2mm; border:1px solid #000; padding:1.5mm;">AVIS DE MENTION DE DÉCÈS</h2>
                <div class="sub">(A transcrire en marge de l'acte de naissance)<br><span class="ar">يوضع في هامش شهادة الميلاد</span></div>
            </div>

            <div style="font-size:7.5pt; margin-top:2mm; line-height:1.4;">
                <b>Adressé à :</b><br>
                M. l'Officier d'Etat-Civil du Centre de naissance de :<br>
                <u>{{ $act->deceased_birth_place ?? 'Moundou' }}</u>
            </div>

            <div style="font-size:7.5pt; margin-top:3mm; line-height:1.5; border:1px solid #ddd; padding:2mm; border-radius:3px;">
                Déclare que le décès enregistré sous le <b>N° {{ $act->registry_number }} / {{ $act->registry_year }}</b>,<br>
                survenu à <b>{{ $act->death_place }}</b> le <b>{{ $act->death_date->format('d/m/Y') }}</b>,<br>
                de : <b>{{ $act->deceased_first_names }} {{ $act->deceased_last_name }}</b>,<br>
                né(e) à : {{ $act->deceased_birth_place }} le {{ $act->deceased_birth_date }},<br>
                fils/fille de {{ $act->father_name }} et de {{ $act->mother_name }},<br>
                Acte de naissance N° <u>{{ $act->birth_act_ref ?? '_____' }}</u>,<br>
                a été régulièrement inscrit sur les registres d'état civil.
            </div>

            <div style="font-size:7pt; margin-top:3mm; font-style:italic;">
                Prière de mentionner ce décès en marge de l'acte de naissance de l'intéressé(e) et de renvoyer le récépissé ci-dessous.
            </div>
        </div>

        <div class="sig-block" style="border-top:1px solid #000; pt:2mm;">
            <div style="font-size:7pt; color:#666;">Code vérif : {{ $act->verification_code }}</div>
            <div style="text-align:right;">
                <div>L'Officier d'Etat-Civil</div>
                <div style="margin-top:15px; font-size:6.5pt; color:#777;">(Signature et Cachet)</div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
