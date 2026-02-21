<?php

declare(strict_types=1);

/*
 * This file is part of fab2s/searchable.
 * (c) Fabrice de Stefanis / https://github.com/fab2s/Searchable
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\Searchable\Phonetic;

use Normalizer;

/**
 * French phonetic algorithm by Edouard Berge.
 *
 * Optimized port from Talisman (MIT) - https://github.com/Yomguithereal/talisman
 *
 * @see http://www.roudoudou.com/phonetic.php
 */
class Phonetic implements PhoneticInterface
{
    /** @var list<string> */
    protected const FIRST_PATTERNS = ['~O[O]+~', '~SAOU~', '~OES~', '~CCH~', '~CC([IYE])~', '~(.)\1~'];

    /** @var list<string> */
    protected const FIRST_REPLACEMENTS = ['OU', 'SOU', 'OS', 'K', 'KS$1', '$1'];

    /** @var list<string> */
    protected const MAIN_PATTERNS = ['~OIN[GT]$~', '~E[RS]$~', '~(C|CH)OEU~', '~MOEU~', '~OE([UI]+)([BCDFGHJKLMNPQRSTVWXZ])~', '~^GEN[TS]$~', '~CUEI~', '~([^AEIOUYC])AE([BCDFGHJKLMNPQRSTVWXZ])~', '~AE([QS])~', '~AIE([BCDFGJKLMNPQRSTVWXZ])~', '~ANIEM~', '~(DRA|TRO|IRO)P$~', '~(LOM)B$~', '~(RON|POR)C$~', '~PECT$~', '~ECUL$~', '~(CHA|CA|E)M(P|PS)$~', '~(TAN|RAN)G$~', '~([^VO])ILAG~', '~([^TRH])UIL(AR|E)(.+)~', '~([G])UIL([AEO])~', '~([NSPM])AIL([AEO])~', '~DIL(AI|ON|ER|EM)~', '~RILON~', '~TAILE~', '~GAILET~', '~AIL(A[IR])~', '~OUILA~', '~EIL(AI|AR|ER|EM)~', '~REILET~', '~EILET~', '~AILOL~', '~([^AEIOUY])(SC|S)IEM([EA])~', '~^(SC|S)IEM([EA])~', '~([OAI])MB~', '~([OA])MP~', '~GEMB~', '~EM([BP])~', '~UMBL~', '~CIEN~', '~^ECEUR~', '~^CH(OG+|OL+|OR+|EU+|ARIS|M+|IRO|ONDR)~', '~(YN|RI)CH(OG+|OL+|OC+|OP+|OM+|ARIS|M+|IRO|ONDR)~', '~CHS~', '~CH(AIQ)~', '~^ECHO([^UIPY])~', '~ISCH(I|E)~', '~^ICHT~', '~ORCHID~', '~ONCHIO~', '~ACHIA~', '~([^C])ANICH~', '~OMANIK~', '~ACHY([^D])~', '~([AEIOU])C([BDFGJKLMNPQRTVWXZ])~', '~EUCHA~', '~YCH(IA|A|O|ED)~', '~([AR])CHEO~', '~RCHES~', '~ECHN~', '~OCHTO~', '~CHO(RA|NDR|RE)~', '~MACHM~', '~BRONCHO~', '~LICHO([SC])~', '~WA~', '~WO~', '~(?:WI|WHI|WHY)~', '~WHA~', '~WHO~', '~GNE([STR])~', '~GNE~', '~GI~', '~GNI~', '~GN(A|OU|UR)~', '~GY~', '~OUGAIN~', '~AGEO([LT])~', '~GEORG~', '~GEO(LO|M|P|G|S|R)~', '~([NU])GEOT~', '~GEO([TDC])~', '~GE([OA])~', '~GE~', '~QU?~', '~C[YI]~', '~CN~', '~ICM~', '~CEAT~', '~CE~', '~C([RO])~', '~CUEI~', '~CU~', '~VENCA~', '~C([AS])~', '~CLEN~', '~C([LZ])~', '~CTIQ~', '~CTI[CS]~', '~CTI([FL])~', '~CTIO~', '~CT([IUEOR])?~', '~PH~', '~TH~', '~OW~', '~LH~', '~RDL~', '~CH(LO|R)~', '~PTIA~', '~GU([^RLMBSTPZN])~', '~GNO(?=[MLTNRKG])~', '~BUTI([EA])~', '~BATIA~', '~ANTIEL~', '~RETION~', '~ENTI([EA])L~', '~ENTIO~', '~ENTIAI~', '~UJETION~', '~ATIEM~', '~PETIEN~', '~CETIE~', '~OFETIE~', '~IPETI~', '~LBUTION~', '~BLUTION~', '~L([EA])TION~', '~SATIET~', '~(.+)ANTI(AL|O)~', '~(.+)INUTI([^V])~', '~([^O])UTIEN~', '~([^DE])RATI([E])$~', '~([^SNEU]|KU|KO|RU|LU|BU|TU|AU)T(IEN|ION)~', '~([^CS])H~', '~([EN])SH~', '~SH~', '~OMT~', '~IM([BP])~', '~UMD~', '~([TRD])IENT~', '~IEN~', '~YM([UOAEIN])~', '~YM~', '~AHO~', '~([FDS])AIM~', '~EIN~', '~AINS~', '~AIN$~', '~AIN([BTDK])~', '~([^O])UND~', '~([JTVLFMRPSBD])UN([^IAE])~', '~([JTVLFMRPSBD])UN$~', '~RFUM$~', '~LUMB~', '~([^BCDFGHJKLMNPQRSTVWXZ])EN~', '~([VTLJMRPDSBFKNG])EN(?=[BRCTDKZSVN])~', '~^EN([BCDFGHJKLNPQRSTVXZ]|CH|IV|ORG|OB|UI|UA|UY)~', '~(^[JRVTH])EN([DRTFGSVJMP])~', '~SEN([ST])~', '~^DESENIV~', '~([^M])EN(U[IY])~', '~(.+[JTVLFMRPSBD])EN([JLFDSTG])~', '~([VSBSTNRLPM])E[IY]([ACDFRJLGZ])~', '~EAU~', '~EU~', '~Y~', '~EOI~', '~JEA~', '~OIEM~', '~OUANJ~', '~OUA~', '~OUENJ~', '~AU([^E])~', '~^BENJ~', '~RTIEL~', '~PINK~', '~KIND~', '~KUM(N|P)~', '~LKOU~', '~EDBE~', '~ARCM~', '~SCH~', '~^OINI~', '~([^NDCGRHKO])APT~', '~([L]|KON)PT~', '~OTB~', '~IXA~', '~TG~', '~^TZ~', '~PTIE~', '~GT~', '~ANKIEM~', '~(LO|RE)KEMAN~', '~NT(B|M)~', '~GSU~', '~ESD~', '~LESKEL~', '~CK~', '~USIL$~', '~X$|[TD]S$|[DS]$~', '~([^KL]+)T$~', '~^[H]~'];

    /** @var list<string> */
    protected const MAIN_REPLACEMENTS = ['OIN', 'E', 'KE', 'ME', 'E$1$2', 'JAN', 'KEI', '$1E$2', 'E$1', 'AI$1', 'ANIM', '$1', '$1', '$1', 'PET', 'CU', '$1N', '$1', '$1IAJ', '$1UI$2$3', '$1UI$2', '$1AI$2', 'DI$1', 'RION', 'TAIE', 'GAIET', 'AI$1', 'OUIA', 'AI$1', 'RAIET', 'EIET', 'AIOL', '$1$2IAM$3', '$1IAM$2', '$1NB', '$1NP', 'JANB', 'AN$1', 'INBL', 'SIAN', 'EKEUR', 'K$1', '$1K$2', 'CH', 'K$1', 'EKO$1', 'ISK$1', 'IKT', 'ORKID', 'ONKIO', 'AKIA', '$1ANIK', 'OMANICH', 'AKI$1', '$1K$2', 'EKA', 'IK$1', '$1KEO', 'RKES', 'EKN', 'OKTO', 'KO$1', 'MAKM', 'BRONKO', 'LIKO$1', 'OI', 'O', 'OUI', 'OUA', 'OU', 'NIE$1', 'NE', 'JI', 'NI', 'NI$1', 'JI', 'OUGIN', 'AJO$1', 'JORJ', 'JEO$1', '$1JOT', 'JEO$1', 'J$1', 'JE', 'K', 'SI', 'KN', 'IKM', 'SAT', 'SE', 'K$1', 'KEI', 'KU', 'VANSA', 'K$1', 'KLAN', 'K$1', 'KTIK', 'KTIS', 'KTI$1', 'KSIO', 'KT$1', 'F', 'T', 'OU', 'L', 'RL', 'K$1', 'PSIA', 'G$1', 'NIO', 'BUSI$1', 'BASIA', 'ANSIEL', 'RESION', 'ENSI$1L', 'ENSIO', 'ENSIAI', 'UJESION', 'ASIAM', 'PESIEN', 'CESIE', 'OFESIE', 'IPESI', 'LBUSION', 'BLUSION', 'L$1SION', 'SASIET', '$1ANSI$2', '$1INUSI$2', '$1USIEN', '$1RASI$2', '$1S$2', '$1', '$1S', 'CH', 'ONT', 'IN$1', 'OND', '$1IANT', 'IN', 'IM$1', 'IN', 'AO', '$1IN', 'AIN', 'INS', 'IN', 'IN$1', '$1IND', '$1IN$2', '$1IN', 'RFIN', 'LINB', '$1AN', '$1AN', 'AN$1', '$1AN$2', 'SAN$1', 'DESANIV', '$1AN$2', '$1AN$2', '$1AI$2', 'O', 'E', 'I', 'OI', 'JA', 'OIM', 'OUENJ', 'OI', 'OUANJ', 'O$1', 'BINJ', 'RSIEL', 'PONK', 'KOND', 'KON$1', 'LKO', 'EBE', 'ARKM', 'CH', 'ONI', '$1AT', '$1T', 'OB', 'ISA', 'G', 'TS', 'TIE', 'T', 'ANKILEM', '$1KAMAN', 'N$1', 'SU', 'ED', 'LEKEL', 'K', 'USI', '', '$1', ''];

    /** @var list<string> */
    protected const END_PATTERNS = ['~TIL$~', '~LC$~', '~L[E]?[S]?$~', '~(.+)N[E]?[S]?$~', '~EZ$~', '~OIG$~', '~OUP$~', '~([^R])OM$~', '~LOP$~', '~NTANP$~', '~TUN$~', '~AU$~', '~EI$~', '~R[DG]$~', '~ANC$~', '~KROC$~', '~HOUC$~', '~OMAC$~', '~([J])O([NU])[CG]$~', '~([^GTR])([AO])NG$~', '~UC$~', '~AING$~', '~([EISOARN])C$~', '~([ABD-MO-Z]+)[EH]+$~', '~EN$~', '~(NJ)EN$~', '~^PAIEM~', '~([^NTB])EF$~', '~(.)\1~'];

    /** @var list<string> */
    protected const END_REPLACEMENTS = ['TI', 'LK', 'L', '$1N', 'E', 'OI', 'OU', '$1ON', 'LO', 'NTAN', 'TIN', 'O', 'AI', 'R', 'AN', 'KRO', 'HOU', 'OMA', '$1O$2', '$1$2N', 'UK', 'IN', '$1K', '$1', 'AN', '$1AN', 'PAIM', '$1', '$1'];
    protected const EXCEPTIONS       = [
        'CD'    => 'CD',
        'BD'    => 'BD',
        'BV'    => 'BV',
        'TABAC' => 'TABA',
        'FEU'   => 'FE',
        'FE'    => 'FE',
        'FER'   => 'FER',
        'FIEF'  => 'FIEF',
        'FJORD' => 'FJORD',
        'GOAL'  => 'GOL',
        'FLEAU' => 'FLEO',
        'HIER'  => 'IER',
        'HEU'   => 'E',
        'HE'    => 'E',
        'OS'    => 'OS',
        'RIZ'   => 'RI',
        'RAZ'   => 'RA',
        'ECHO'  => 'EKO',
    ];

    public static function encode(string $word): string
    {
        // Preparing the word
        $word = mb_strtoupper($word);

        // Handle ligatures and cedilla before deburring
        $word = str_replace(
            ['Œ', 'Æ', 'Ç'],
            ['OEU', 'E', 'S'],
            $word,
        );

        // Strip remaining accents
        $normalized = Normalizer::normalize($word, Normalizer::FORM_D);
        if ($normalized !== false) {
            $word = (string) preg_replace('~\p{Mn}~u', '', $normalized);
        }

        // Strip non-alpha
        $word = (string) preg_replace('~[^A-Z]+~', '', $word);

        if ($word === '') {
            return '';
        }

        $code = $word;

        // First preprocessing
        $code = (string) preg_replace(static::FIRST_PATTERNS, static::FIRST_REPLACEMENTS, $code);

        // Check exceptions
        if (isset(static::EXCEPTIONS[$code])) {
            return static::EXCEPTIONS[$code];
        }

        // Second preprocessing + main rules + first endings
        $code = (string) preg_replace(static::MAIN_PATTERNS, static::MAIN_REPLACEMENTS, $code);

        // Save backup for short word recovery
        $backupCode = $code;

        // Second endings
        $code = (string) preg_replace(static::END_PATTERNS, static::END_REPLACEMENTS, $code);

        // Special case
        if ($code === 'FUEL') {
            $code = 'FIOUL';
        }

        // "O" is the only acceptable single-letter code
        if ($code === 'O') {
            return $code;
        }

        // Attempt to save short codes
        if (strlen($code) < 2) {
            // Abbreviations (3+ consecutive consonants)
            if (preg_match('~[BCDFGHJKLMNPQRSTVWXYZ]{3,}~', $word)) {
                return $word;
            }

            // Simple words (consonant + vowel, 3-4 chars)
            if (preg_match('~[RFMLVSPJDF][AEIOU]~', $word)) {
                $len = strlen($word);
                if ($len === 3 || $len === 4) {
                    return substr($word, 0, -1);
                }
            }

            if (strlen($backupCode) > 1) {
                return $backupCode;
            }
        }

        if (strlen($code) > 1) {
            return $code;
        }

        return '';
    }
}
