{{--
    Gabarit par défaut de l'acte de naissance : reproduction du formulaire officiel (Volet N°1 et Volet N°2).
    Page A4 paysage : Volet N°2 à gauche (centre principal), Volet N°1 à droite (parents).
    Textes arabes : config/acts.php. Emblème : déposer l'image dans public/images/emblem.png (facultatif).
    Un gabarit actif en base (Gabarits d'actes) remplace celui-ci.
--}}
@php
    $ar = config('acts.birth.ar');
    $proof = ! config('mairie.act_template_validated');
    $hasEmblem = file_exists(public_path('images/emblem.png'));
    $heure = $d['heure_naissance'] ? ' à ' . str_replace(':', ' h ', $d['heure_naissance']) : '';
    $volets = [
        ['n' => 2, 'ar' => $ar['volet2'], 'note' => '(à envoyer au centre d’Etat civil principal dont dépend le centre secondaire)', 'note_ar' => $ar['note2']],
        ['n' => 1, 'ar' => $ar['volet1'], 'note' => '(à remettre à celui des parents investi de la puissance parentale)', 'note_ar' => $ar['note1']],
    ];
    // fr : libellé français · ar : libellé arabe · v : valeur imprimée (null = pas de ligne, '' = ligne vide)
    $rows = [
        ['fr' => 'Centre d’Etat civil (1)', 'ar' => $ar['centre'], 'v' => null],
        ['fr' => 'de', 'ar' => $ar['de_'], 'v' => $d['centre']],
        ['fr' => 'le (2)', 'ar' => $ar['le'], 'v' => $d['date_declaration_lettres']],
        ['fr' => 's’est présenté (e) (3)', 'ar' => $ar['present'], 'v' => $d['declarant_nom']],
        ['fr' => '', 'ar' => '', 'v' => $d['declarant_qualite']],
        ['fr' => '', 'ar' => '', 'v' => $d['jugement'] ? 'Jugement supplétif : ' . $d['jugement'] : ''],
        ['fr' => '', 'ar' => '', 'v' => ''],
        ['fr' => 'qui a déclaré la naissance d’un', 'ar' => $ar['declared'], 'v' => null],
        ['fr' => 'enfant de sexe', 'ar' => $ar['sex'], 'v' => $d['sexe']],
        ['fr' => 'survenue à', 'ar' => $ar['at'], 'v' => $d['lieu_naissance']],
        ['fr' => 'Rue (4)', 'ar' => $ar['street'], 'v' => $d['rue_naissance']],
        ['fr' => 'Le (5)', 'ar' => $ar['le'], 'v' => $d['date_naissance_lettres'] . $heure],
        ['fr' => 'Nommé (e)', 'ar' => $ar['named'], 'v' => trim($d['nom_enfant'] . ' ' . $d['prenoms_enfant'])],
        ['fr' => '', 'ar' => '', 'v' => ''],
        ['fr' => 'Fils/Fille de (6)', 'ar' => $ar['son'], 'v' => $d['pere_nom']],
        ['fr' => 'Né (e)', 'ar' => $ar['at'], 'v' => $d['pere_lieu_naissance']],
        ['fr' => 'Le', 'ar' => $ar['le'], 'v' => $d['pere_date_naissance']],
        ['fr' => 'Profession', 'ar' => $ar['profession'], 'v' => $d['pere_profession']],
        ['fr' => 'et de (7)', 'ar' => $ar['and_of'], 'v' => $d['mere_nom']],
        ['fr' => 'Née à', 'ar' => $ar['at'], 'v' => $d['mere_lieu_naissance']],
        ['fr' => 'Le', 'ar' => $ar['le'], 'v' => $d['mere_date_naissance']],
        ['fr' => '', 'ar' => '', 'v' => ''],
        ['fr' => 'Profession', 'ar' => $ar['profession'], 'v' => $d['mere_profession']],
        ['fr' => 'Sa légitime épouse', 'ar' => $ar['spouse'], 'v' => ''],
    ];
@endphp
<style>
    @page { size: A4 landscape; margin: 5mm; }
    .doc-sheet { width: 287mm !important; max-width: none !important; min-height: 200mm !important; padding: 0 !important; }
    .ofc { position: relative; display: flex; width: 287mm; height: 200mm; font-family: Arial, Helvetica, sans-serif; color: #000; font-size: 9.6pt; line-height: 1.15; }
    .ofc .volet { position: relative; flex: 1 1 50%; padding: 3mm 8mm 3mm 7mm; box-sizing: border-box; overflow: hidden; }
    .ofc .volet + .volet { border-left: 1px dashed #777; }
    .ofc .flag { position: absolute; top: 0; left: 0; width: 22mm; height: 22mm; clip-path: polygon(0 0, 100% 0, 0 100%);
                 background: linear-gradient(135deg, #002664 0 10%, #FECB00 10% 30%, #C60C30 30% 50%);
                 -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .ofc .top { display: flex; justify-content: space-between; align-items: flex-start; margin-left: 22mm; min-height: 13mm; }
    .ofc .top .t { padding-top: 1mm; }
    .ofc .vn { font-weight: 700; font-size: 11pt; }
    .ofc .ar { font-family: 'Traditional Arabic', 'Noto Naskh Arabic', 'Amiri', 'Segoe UI', Tahoma, Arial, sans-serif; direction: rtl; unicode-bidi: embed; font-size: 11.5pt; white-space: nowrap; }
    .ofc .emblem { width: 14mm; height: 14mm; border-radius: 50%; object-fit: cover; }
    .ofc .rep { display: flex; justify-content: space-between; align-items: flex-start; margin-top: 1mm; }
    .ofc .rep small { display: block; font-size: 8.4pt; }
    .ofc .note { text-align: center; font-style: italic; font-size: 8pt; margin: 1.2mm 0 0; }
    .ofc .note-ar { text-align: center; direction: rtl; font-size: 9.5pt; margin: 0 0 1.5mm; }
    .ofc .title { display: flex; align-items: flex-end; justify-content: space-between; margin: 0.5mm 0 1.6mm; font-weight: 700; font-size: 11pt; }
    .ofc .title .num { flex: 1; border-bottom: 1px solid #000; margin: 0 2mm; text-align: center; font-weight: 400; min-height: 5mm; }
    .ofc .r { display: grid; grid-template-columns: auto 1fr auto; align-items: end; column-gap: 1.6mm; min-height: 5.35mm; }
    .ofc .r .fr { white-space: nowrap; }
    .ofc .r .val { border-bottom: 1px solid #000; min-height: 4.3mm; padding: 0 1mm; font-weight: 700; font-size: 10pt; overflow: hidden; text-overflow: clip; }
    .ofc .r .val.nolabel { grid-column: 1 / -1; }
    .ofc .r.head .fr, .ofc .r.head .ar { grid-column: auto; }
    .ofc .sig { display: flex; justify-content: space-between; align-items: flex-start; margin-top: 1.6mm; font-size: 9.4pt; }
    .ofc .sig .ar { font-size: 9pt; }
    .ofc .officer { margin-top: 6mm; font-weight: 700; font-size: 9.6pt; }
    .ofc .verif { position: absolute; left: 7mm; right: 8mm; bottom: 2mm; font-size: 6.8pt; color: #333; display: flex; justify-content: space-between; }
    .ofc .proof { position: absolute; inset: 0; display: grid; place-items: center; pointer-events: none; z-index: 5; }
    .ofc .proof span { transform: rotate(-24deg); font-size: 42pt; font-weight: 700; letter-spacing: 3px; color: rgba(179, 38, 30, .16); white-space: nowrap; }
</style>
<div class="ofc">
    @foreach($volets as $v)
        <section class="volet">
            <div class="flag"></div>
            <div class="top">
                <div class="t"><span class="vn">Volet N°{{ $v['n'] }}</span> &nbsp; <span class="ar">{{ $v['ar'] }}</span></div>
                @if($hasEmblem)<img class="emblem" src="{{ asset('images/emblem.png') }}" alt="">@endif
            </div>
            <div class="rep">
                <div>République du Tchad<small>Unité - Travail - Progrès</small></div>
                <div class="ar" style="text-align:right">{{ $ar['republic'] }}<br>{{ $ar['motto'] }}</div>
            </div>
            <p class="note">{{ $v['note'] }}</p>
            <p class="note-ar ar">{{ $v['note_ar'] }}</p>
            <div class="title"><span>Acte de naissance N°</span><span class="num">{{ $d['numero_acte'] }}</span><span class="ar">{{ $ar['title'] }}</span></div>
            @foreach($rows as $row)
                @if($row['v'] === null)
                    <div class="r"><span class="fr">{{ $row['fr'] }}</span><span></span><span class="ar">{{ $row['ar'] }}</span></div>
                @elseif($row['fr'] === '')
                    <div class="r"><span class="val nolabel">{{ $row['v'] }}</span></div>
                @else
                    <div class="r"><span class="fr">{{ $row['fr'] }}</span><span class="val">{{ $row['v'] }}</span><span class="ar">{{ $row['ar'] }}</span></div>
                @endif
            @endforeach
            <div class="sig">
                <span><i>L’Officier d’Etat Civil</i></span>
                <span class="ar">{{ $ar['officer'] }}</span>
            </div>
            <div class="officer">{{ $d['officier'] }}</div>
            <div class="verif"><span>Copie n° {{ $d['numero_copie'] }} — éditée le {{ $d['date_edition'] }}</span><span>Vérification : {{ $d['code_verification'] }} · {{ str_replace('https://', '', $d['url_verification']) }}</span></div>
        </section>
    @endforeach
    @if($proof)<div class="proof"><span>ÉPREUVE — NON VALIDÉ</span></div>@endif
</div>
