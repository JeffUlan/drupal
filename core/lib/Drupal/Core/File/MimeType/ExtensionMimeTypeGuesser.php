<?php

namespace Drupal\Core\File\MimeType;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\Mime\MimeTypeGuesserInterface;

/**
 * Makes possible to guess the MIME type of a file using its extension.
 */
class ExtensionMimeTypeGuesser implements MimeTypeGuesserInterface {

  /**
   * Default MIME extension mapping.
   *
   * @var array
   *   Array of mimetypes correlated to the extensions that relate to them.
   */
  protected $defaultMapping = [
    'mimetypes' => [
      0 => 'application/andrew-inset',
      1 => 'application/atom',
      2 => 'application/atomcat+xml',
      3 => 'application/atomserv+xml',
      4 => 'application/cap',
      5 => 'application/cu-seeme',
      6 => 'application/dsptype',
      350 => 'application/epub+zip',
      359 => 'application/gzip',
      7 => 'application/hta',
      8 => 'application/java-archive',
      9 => 'application/java-serialized-object',
      10 => 'application/java-vm',
      11 => 'application/mac-binhex40',
      12 => 'application/mathematica',
      13 => 'application/msaccess',
      14 => 'application/msword',
      15 => 'application/octet-stream',
      16 => 'application/oda',
      17 => 'application/ogg',
      18 => 'application/pdf',
      19 => 'application/pgp-keys',
      20 => 'application/pgp-signature',
      21 => 'application/pics-rules',
      22 => 'application/postscript',
      23 => 'application/rar',
      24 => 'application/rdf+xml',
      25 => 'application/rss+xml',
      26 => 'application/rtf',
      27 => 'application/smil',
      349 => 'application/vnd.amazon.ebook',
      28 => 'application/vnd.cinderella',
      29 => 'application/vnd.google-earth.kml+xml',
      30 => 'application/vnd.google-earth.kmz',
      31 => 'application/vnd.mozilla.xul+xml',
      32 => 'application/vnd.ms-excel',
      33 => 'application/vnd.ms-excel.addin.macroEnabled.12',
      34 => 'application/vnd.ms-excel.sheet.binary.macroEnabled.12',
      35 => 'application/vnd.ms-excel.sheet.macroEnabled.12',
      36 => 'application/vnd.ms-excel.template.macroEnabled.12',
      37 => 'application/vnd.ms-pki.seccat',
      38 => 'application/vnd.ms-pki.stl',
      39 => 'application/vnd.ms-powerpoint',
      40 => 'application/vnd.ms-powerpoint.addin.macroEnabled.12',
      41 => 'application/vnd.ms-powerpoint.presentation.macroEnabled.12',
      42 => 'application/vnd.ms-powerpoint.slideshow.macroEnabled.12',
      43 => 'application/vnd.ms-powerpoint.template.macroEnabled.12',
      44 => 'application/vnd.ms-word.document.macroEnabled.12',
      45 => 'application/vnd.ms-word.template.macroEnabled.12',
      46 => 'application/vnd.ms-xpsdocument',
      47 => 'application/vnd.oasis.opendocument.chart',
      48 => 'application/vnd.oasis.opendocument.database',
      49 => 'application/vnd.oasis.opendocument.formula',
      50 => 'application/vnd.oasis.opendocument.graphics',
      51 => 'application/vnd.oasis.opendocument.graphics-template',
      52 => 'application/vnd.oasis.opendocument.image',
      53 => 'application/vnd.oasis.opendocument.presentation',
      54 => 'application/vnd.oasis.opendocument.presentation-template',
      55 => 'application/vnd.oasis.opendocument.spreadsheet',
      56 => 'application/vnd.oasis.opendocument.spreadsheet-template',
      57 => 'application/vnd.oasis.opendocument.text',
      58 => 'application/vnd.oasis.opendocument.text-master',
      59 => 'application/vnd.oasis.opendocument.text-template',
      60 => 'application/vnd.oasis.opendocument.text-web',
      61 => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
      62 => 'application/vnd.openxmlformats-officedocument.presentationml.slideshow',
      63 => 'application/vnd.openxmlformats-officedocument.presentationml.template',
      64 => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
      65 => 'application/vnd.openxmlformats-officedocument.spreadsheetml.template',
      66 => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
      67 => 'application/vnd.openxmlformats-officedocument.wordprocessingml.template',
      68 => 'application/vnd.rim.cod',
      69 => 'application/vnd.smaf',
      70 => 'application/vnd.stardivision.calc',
      71 => 'application/vnd.stardivision.chart',
      72 => 'application/vnd.stardivision.draw',
      73 => 'application/vnd.stardivision.impress',
      74 => 'application/vnd.stardivision.math',
      75 => 'application/vnd.stardivision.writer',
      76 => 'application/vnd.stardivision.writer-global',
      77 => 'application/vnd.sun.xml.calc',
      78 => 'application/vnd.sun.xml.calc.template',
      79 => 'application/vnd.sun.xml.draw',
      80 => 'application/vnd.sun.xml.draw.template',
      81 => 'application/vnd.sun.xml.impress',
      82 => 'application/vnd.sun.xml.impress.template',
      83 => 'application/vnd.sun.xml.math',
      84 => 'application/vnd.sun.xml.writer',
      85 => 'application/vnd.sun.xml.writer.global',
      86 => 'application/vnd.sun.xml.writer.template',
      87 => 'application/vnd.symbian.install',
      88 => 'application/vnd.visio',
      89 => 'application/vnd.wap.wbxml',
      90 => 'application/vnd.wap.wmlc',
      91 => 'application/vnd.wap.wmlscriptc',
      92 => 'application/wordperfect',
      93 => 'application/wordperfect5.1',
      94 => 'application/x-123',
      95 => 'application/x-7z-compressed',
      96 => 'application/x-abiword',
      97 => 'application/x-apple-diskimage',
      98 => 'application/x-bcpio',
      99 => 'application/x-bittorrent',
      100 => 'application/x-cab',
      101 => 'application/x-cbr',
      102 => 'application/x-cbz',
      103 => 'application/x-cdf',
      104 => 'application/x-cdlink',
      105 => 'application/x-chess-pgn',
      106 => 'application/x-cpio',
      107 => 'application/x-debian-package',
      108 => 'application/x-director',
      109 => 'application/x-dms',
      110 => 'application/x-doom',
      111 => 'application/x-dvi',
      113 => 'application/x-font',
      114 => 'application/x-freemind',
      115 => 'application/x-futuresplash',
      116 => 'application/x-gnumeric',
      117 => 'application/x-go-sgf',
      118 => 'application/x-graphing-calculator',
      119 => 'application/x-gtar',
      120 => 'application/x-hdf',
      121 => 'application/x-httpd-eruby',
      122 => 'application/x-httpd-php',
      123 => 'application/x-httpd-php-source',
      124 => 'application/x-httpd-php3',
      125 => 'application/x-httpd-php3-preprocessed',
      126 => 'application/x-httpd-php4',
      127 => 'application/x-ica',
      128 => 'application/x-internet-signup',
      129 => 'application/x-iphone',
      130 => 'application/x-iso9660-image',
      131 => 'application/x-java-jnlp-file',
      132 => 'text/javascript',
      133 => 'application/x-jmol',
      134 => 'application/x-kchart',
      135 => 'application/x-killustrator',
      136 => 'application/x-koan',
      137 => 'application/x-kpresenter',
      138 => 'application/x-kspread',
      139 => 'application/x-kword',
      140 => 'application/x-latex',
      141 => 'application/x-lha',
      142 => 'application/x-lyx',
      143 => 'application/x-lzh',
      144 => 'application/x-lzx',
      145 => 'application/x-maker',
      146 => 'application/x-mif',
      351 => 'application/x-mobipocket-ebook',
      352 => 'application/x-mobipocket-ebook',
      147 => 'application/x-ms-wmd',
      148 => 'application/x-ms-wmz',
      149 => 'application/x-msdos-program',
      150 => 'application/x-msi',
      151 => 'application/x-netcdf',
      152 => 'application/x-ns-proxy-autoconfig',
      153 => 'application/x-nwc',
      154 => 'application/x-object',
      155 => 'application/x-oz-application',
      156 => 'application/x-pkcs7-certreqresp',
      157 => 'application/x-pkcs7-crl',
      158 => 'application/x-python-code',
      159 => 'application/x-quicktimeplayer',
      160 => 'application/x-redhat-package-manager',
      161 => 'application/x-shar',
      162 => 'application/x-shockwave-flash',
      163 => 'application/x-stuffit',
      164 => 'application/x-sv4cpio',
      165 => 'application/x-sv4crc',
      166 => 'application/x-tar',
      167 => 'application/x-tcl',
      168 => 'application/x-tex-gf',
      169 => 'application/x-tex-pk',
      170 => 'application/x-texinfo',
      171 => 'application/x-trash',
      172 => 'application/x-troff',
      173 => 'application/x-troff-man',
      174 => 'application/x-troff-me',
      175 => 'application/x-troff-ms',
      176 => 'application/x-ustar',
      177 => 'application/x-wais-source',
      178 => 'application/x-wingz',
      179 => 'application/x-x509-ca-cert',
      180 => 'application/x-xcf',
      181 => 'application/x-xfig',
      182 => 'application/x-xpinstall',
      183 => 'application/xhtml+xml',
      184 => 'application/xml',
      185 => 'application/zip',
      360 => 'audio/aac',
      186 => 'audio/basic',
      112 => 'audio/flac',
      187 => 'audio/midi',
      346 => 'audio/mp4',
      188 => 'audio/mpeg',
      189 => 'audio/ogg',
      190 => 'audio/prs.sid',
      356 => 'audio/webm',
      191 => 'audio/x-aiff',
      192 => 'audio/x-gsm',
      354 => 'audio/x-matroska',
      193 => 'audio/x-mpegurl',
      194 => 'audio/x-ms-wax',
      195 => 'audio/x-ms-wma',
      196 => 'audio/x-pn-realaudio',
      197 => 'audio/x-realaudio',
      198 => 'audio/x-scpls',
      199 => 'audio/x-sd2',
      200 => 'audio/x-wav',
      201 => 'chemical/x-alchemy',
      202 => 'chemical/x-cache',
      203 => 'chemical/x-cache-csf',
      204 => 'chemical/x-cactvs-binary',
      205 => 'chemical/x-cdx',
      206 => 'chemical/x-cerius',
      207 => 'chemical/x-chem3d',
      208 => 'chemical/x-chemdraw',
      209 => 'chemical/x-cif',
      210 => 'chemical/x-cmdf',
      211 => 'chemical/x-cml',
      212 => 'chemical/x-compass',
      213 => 'chemical/x-crossfire',
      214 => 'chemical/x-csml',
      215 => 'chemical/x-ctx',
      216 => 'chemical/x-cxf',
      217 => 'chemical/x-embl-dl-nucleotide',
      218 => 'chemical/x-galactic-spc',
      219 => 'chemical/x-gamess-input',
      220 => 'chemical/x-gaussian-checkpoint',
      221 => 'chemical/x-gaussian-cube',
      222 => 'chemical/x-gaussian-input',
      223 => 'chemical/x-gaussian-log',
      224 => 'chemical/x-gcg8-sequence',
      225 => 'chemical/x-genbank',
      226 => 'chemical/x-hin',
      227 => 'chemical/x-isostar',
      228 => 'chemical/x-jcamp-dx',
      229 => 'chemical/x-kinemage',
      230 => 'chemical/x-macmolecule',
      231 => 'chemical/x-macromodel-input',
      232 => 'chemical/x-mdl-molfile',
      233 => 'chemical/x-mdl-rdfile',
      234 => 'chemical/x-mdl-rxnfile',
      235 => 'chemical/x-mdl-sdfile',
      236 => 'chemical/x-mdl-tgf',
      237 => 'chemical/x-mmcif',
      238 => 'chemical/x-mol2',
      239 => 'chemical/x-molconn-Z',
      240 => 'chemical/x-mopac-graph',
      241 => 'chemical/x-mopac-input',
      242 => 'chemical/x-mopac-out',
      243 => 'chemical/x-mopac-vib',
      244 => 'chemical/x-ncbi-asn1-ascii',
      245 => 'chemical/x-ncbi-asn1-binary',
      246 => 'chemical/x-ncbi-asn1-spec',
      247 => 'chemical/x-pdb',
      248 => 'chemical/x-rosdal',
      249 => 'chemical/x-swissprot',
      250 => 'chemical/x-vamas-iso14976',
      251 => 'chemical/x-vmd',
      252 => 'chemical/x-xtel',
      253 => 'chemical/x-xyz',
      254 => 'image/gif',
      255 => 'image/ief',
      256 => 'image/jpeg',
      257 => 'image/pcx',
      258 => 'image/png',
      259 => 'image/svg+xml',
      260 => 'image/tiff',
      261 => 'image/vnd.djvu',
      262 => 'image/vnd.microsoft.icon',
      263 => 'image/vnd.wap.wbmp',
      355 => 'image/webp',
      264 => 'image/x-cmu-raster',
      265 => 'image/x-coreldraw',
      266 => 'image/x-coreldrawpattern',
      267 => 'image/x-coreldrawtemplate',
      268 => 'image/x-corelphotopaint',
      269 => 'image/x-jg',
      270 => 'image/x-jng',
      271 => 'image/x-ms-bmp',
      272 => 'image/x-photoshop',
      273 => 'image/x-portable-anymap',
      274 => 'image/x-portable-bitmap',
      275 => 'image/x-portable-graymap',
      276 => 'image/x-portable-pixmap',
      277 => 'image/x-rgb',
      278 => 'image/x-xbitmap',
      279 => 'image/x-xpixmap',
      280 => 'image/x-xwindowdump',
      281 => 'message/rfc822',
      282 => 'model/iges',
      283 => 'model/mesh',
      284 => 'model/vrml',
      285 => 'text/calendar',
      286 => 'text/css',
      287 => 'text/csv',
      288 => 'text/h323',
      289 => 'text/html',
      290 => 'text/iuls',
      291 => 'text/mathml',
      292 => 'text/plain',
      293 => 'text/richtext',
      294 => 'text/scriptlet',
      295 => 'text/tab-separated-values',
      296 => 'text/texmacs',
      297 => 'text/vnd.sun.j2me.app-descriptor',
      298 => 'text/vnd.wap.wml',
      299 => 'text/vnd.wap.wmlscript',
      358 => 'text/vtt',
      300 => 'text/x-bibtex',
      301 => 'text/x-boo',
      302 => 'text/x-c++hdr',
      303 => 'text/x-c++src',
      304 => 'text/x-chdr',
      305 => 'text/x-component',
      306 => 'text/x-csh',
      307 => 'text/x-csrc',
      308 => 'text/x-diff',
      309 => 'text/x-dsrc',
      310 => 'text/x-haskell',
      311 => 'text/x-java',
      312 => 'text/x-literate-haskell',
      313 => 'text/x-moc',
      314 => 'text/x-pascal',
      315 => 'text/x-pcs-gcd',
      316 => 'text/x-perl',
      317 => 'text/x-python',
      318 => 'text/x-setext',
      319 => 'text/x-sh',
      320 => 'text/x-tcl',
      321 => 'text/x-tex',
      322 => 'text/x-vcalendar',
      323 => 'text/x-vcard',
      324 => 'video/3gpp',
      325 => 'video/dl',
      326 => 'video/dv',
      327 => 'video/fli',
      328 => 'video/gl',
      329 => 'video/mp4',
      330 => 'video/mpeg',
      331 => 'video/ogg',
      332 => 'video/quicktime',
      333 => 'video/vnd.mpegurl',
      357 => 'video/webm',
      347 => 'video/x-flv',
      334 => 'video/x-la-asf',
      348 => 'video/x-m4v',
      353 => 'video/x-matroska',
      335 => 'video/x-mng',
      336 => 'video/x-ms-asf',
      337 => 'video/x-ms-wm',
      338 => 'video/x-ms-wmv',
      339 => 'video/x-ms-wmx',
      340 => 'video/x-ms-wvx',
      341 => 'video/x-msvideo',
      342 => 'video/x-sgi-movie',
      343 => 'x-conference/x-cooltalk',
      344 => 'x-epoc/x-sisx-app',
      345 => 'x-world/x-vrml',
      361 => 'application/json',
    ],

    // Extensions added to this list MUST be lower-case.
    'extensions' => [
      'ez' => 0,
      'atom' => 1,
      'atomcat' => 2,
      'atomsrv' => 3,
      'cap' => 4,
      'pcap' => 4,
      'cu' => 5,
      'tsp' => 6,
      'hta' => 7,
      'jar' => 8,
      'ser' => 9,
      'class' => 10,
      'hqx' => 11,
      'nb' => 12,
      'mdb' => 13,
      'dot' => 14,
      'doc' => 14,
      'bin' => 15,
      'oda' => 16,
      'ogx' => 17,
      'pdf' => 18,
      'key' => 19,
      'pgp' => 20,
      'prf' => 21,
      'eps' => 22,
      'ai' => 22,
      'ps' => 22,
      'rar' => 23,
      'rdf' => 24,
      'rss' => 25,
      'rtf' => 26,
      'smi' => 27,
      'smil' => 27,
      'cdy' => 28,
      'kml' => 29,
      'kmz' => 30,
      'xul' => 31,
      'xlb' => 32,
      'xlt' => 32,
      'xls' => 32,
      'xlam' => 33,
      'xlsb' => 34,
      'xlsm' => 35,
      'xltm' => 36,
      'cat' => 37,
      'stl' => 38,
      'pps' => 39,
      'ppt' => 39,
      'ppam' => 40,
      'pptm' => 41,
      'ppsm' => 42,
      'potm' => 43,
      'docm' => 44,
      'dotm' => 45,
      'xps' => 46,
      'odc' => 47,
      'odb' => 48,
      'odf' => 49,
      'odg' => 50,
      'otg' => 51,
      'odi' => 52,
      'odp' => 53,
      'otp' => 54,
      'ods' => 55,
      'ots' => 56,
      'odt' => 57,
      'odm' => 58,
      'ott' => 59,
      'oth' => 60,
      'pptx' => 61,
      'ppsx' => 62,
      'potx' => 63,
      'xlsx' => 64,
      'xltx' => 65,
      'docx' => 66,
      'dotx' => 67,
      'cod' => 68,
      'mmf' => 69,
      'sdc' => 70,
      'sds' => 71,
      'sda' => 72,
      'sdd' => 73,
      'sdw' => 75,
      'sgl' => 76,
      'sxc' => 77,
      'stc' => 78,
      'sxd' => 79,
      'std' => 80,
      'sxi' => 81,
      'sti' => 82,
      'sxm' => 83,
      'sxw' => 84,
      'sxg' => 85,
      'stw' => 86,
      'sis' => 87,
      'vsd' => 88,
      'wbxml' => 89,
      'wmlc' => 90,
      'wmlsc' => 91,
      'wpd' => 92,
      'wp5' => 93,
      'wk' => 94,
      '7z' => 95,
      'abw' => 96,
      'dmg' => 97,
      'bcpio' => 98,
      'torrent' => 99,
      'cab' => 100,
      'cbr' => 101,
      'cbz' => 102,
      'cdf' => 103,
      'vcd' => 104,
      'pgn' => 105,
      'cpio' => 106,
      'udeb' => 107,
      'deb' => 107,
      'dir' => 108,
      'dxr' => 108,
      'dcr' => 108,
      'dms' => 109,
      'wad' => 110,
      'dvi' => 111,
      'flac' => 112,
      'pfa' => 113,
      'pfb' => 113,
      'pcf' => 113,
      'gsf' => 113,
      'pcf.z' => 113,
      'mm' => 114,
      'spl' => 115,
      'gnumeric' => 116,
      'sgf' => 117,
      'gcf' => 118,
      'taz' => 119,
      'gtar' => 119,
      'tgz' => 119,
      'hdf' => 120,
      'rhtml' => 121,
      'phtml' => 122,
      'pht' => 122,
      'php' => 122,
      'phps' => 123,
      'php3' => 124,
      'php3p' => 125,
      'php4' => 126,
      'ica' => 127,
      'ins' => 128,
      'isp' => 128,
      'iii' => 129,
      'iso' => 130,
      'jnlp' => 131,
      'js' => 132,
      'jmz' => 133,
      'chrt' => 134,
      'kil' => 135,
      'skp' => 136,
      'skd' => 136,
      'skm' => 136,
      'skt' => 136,
      'kpr' => 137,
      'kpt' => 137,
      'ksp' => 138,
      'kwd' => 139,
      'kwt' => 139,
      'latex' => 140,
      'lha' => 141,
      'lyx' => 142,
      'lzh' => 143,
      'lzx' => 144,
      'maker' => 145,
      'frm' => 145,
      'frame' => 145,
      'fm' => 145,
      'book' => 145,
      'fb' => 145,
      'fbdoc' => 145,
      'mif' => 146,
      'wmd' => 147,
      'wmz' => 148,
      'dll' => 149,
      'bat' => 149,
      'exe' => 149,
      'com' => 149,
      'msi' => 150,
      'nc' => 151,
      'pac' => 152,
      'nwc' => 153,
      'o' => 154,
      'oza' => 155,
      'p7r' => 156,
      'crl' => 157,
      'pyo' => 158,
      'pyc' => 158,
      'qtl' => 159,
      'rpm' => 160,
      'shar' => 161,
      'swf' => 162,
      'swfl' => 162,
      'sitx' => 163,
      'sit' => 163,
      'sv4cpio' => 164,
      'sv4crc' => 165,
      'tar' => 166,
      'gf' => 168,
      'pk' => 169,
      'texi' => 170,
      'texinfo' => 170,
      'sik' => 171,
      '~' => 171,
      'bak' => 171,
      '%' => 171,
      'old' => 171,
      't' => 172,
      'roff' => 172,
      'tr' => 172,
      'man' => 173,
      'me' => 174,
      'ms' => 175,
      'ustar' => 176,
      'src' => 177,
      'wz' => 178,
      'crt' => 179,
      'xcf' => 180,
      'fig' => 181,
      'xpi' => 182,
      'xht' => 183,
      'xhtml' => 183,
      'xml' => 184,
      'xsl' => 184,
      'zip' => 185,
      'au' => 186,
      'snd' => 186,
      'mid' => 187,
      'midi' => 187,
      'kar' => 187,
      'mpega' => 188,
      'mpga' => 188,
      'mp3' => 188,
      'mp2' => 188,
      'ogg' => 189,
      'oga' => 189,
      'opus' => 189,
      'spx' => 189,
      'sid' => 190,
      'aif' => 191,
      'aiff' => 191,
      'aifc' => 191,
      'gsm' => 192,
      'm3u' => 193,
      'wax' => 194,
      'wma' => 195,
      'rm' => 196,
      'ram' => 196,
      'ra' => 197,
      'pls' => 198,
      'sd2' => 199,
      'wav' => 200,
      'alc' => 201,
      'cac' => 202,
      'cache' => 202,
      'csf' => 203,
      'cascii' => 204,
      'cbin' => 204,
      'ctab' => 204,
      'cdx' => 205,
      'cer' => 206,
      'c3d' => 207,
      'chm' => 208,
      'cif' => 209,
      'cmdf' => 210,
      'cml' => 211,
      'cpa' => 212,
      'bsd' => 213,
      'csml' => 214,
      'csm' => 214,
      'ctx' => 215,
      'cxf' => 216,
      'cef' => 216,
      'emb' => 217,
      'embl' => 217,
      'spc' => 218,
      'gam' => 219,
      'inp' => 219,
      'gamin' => 219,
      'fchk' => 220,
      'fch' => 220,
      'cub' => 221,
      'gau' => 222,
      'gjf' => 222,
      'gjc' => 222,
      'gal' => 223,
      'gcg' => 224,
      'gen' => 225,
      'hin' => 226,
      'istr' => 227,
      'ist' => 227,
      'dx' => 228,
      'jdx' => 228,
      'kin' => 229,
      'mcm' => 230,
      'mmd' => 231,
      'mmod' => 231,
      'mol' => 232,
      'rd' => 233,
      'rxn' => 234,
      'sdf' => 235,
      'sd' => 235,
      'tgf' => 236,
      'mcif' => 237,
      'mol2' => 238,
      'b' => 239,
      'gpt' => 240,
      'mopcrt' => 241,
      'zmt' => 241,
      'mpc' => 241,
      'dat' => 241,
      'mop' => 241,
      'moo' => 242,
      'mvb' => 243,
      'prt' => 244,
      'aso' => 245,
      'val' => 245,
      'asn' => 246,
      'ent' => 247,
      'pdb' => 247,
      'ros' => 248,
      'sw' => 249,
      'vms' => 250,
      'vmd' => 251,
      'xtel' => 252,
      'xyz' => 253,
      'gif' => 254,
      'ief' => 255,
      'jpeg' => 256,
      'jpe' => 256,
      'jpg' => 256,
      'pcx' => 257,
      'png' => 258,
      'svgz' => 259,
      'svg' => 259,
      'tif' => 260,
      'tiff' => 260,
      'djvu' => 261,
      'djv' => 261,
      'ico' => 262,
      'wbmp' => 263,
      'ras' => 264,
      'cdr' => 265,
      'pat' => 266,
      'cdt' => 267,
      'cpt' => 268,
      'art' => 269,
      'jng' => 270,
      'bmp' => 271,
      'psd' => 272,
      'pnm' => 273,
      'pbm' => 274,
      'pgm' => 275,
      'ppm' => 276,
      'rgb' => 277,
      'xbm' => 278,
      'xpm' => 279,
      'xwd' => 280,
      'eml' => 281,
      'igs' => 282,
      'iges' => 282,
      'silo' => 283,
      'msh' => 283,
      'mesh' => 283,
      'icz' => 285,
      'ics' => 285,
      'css' => 286,
      'csv' => 287,
      '323' => 288,
      'html' => 289,
      'htm' => 289,
      'shtml' => 289,
      'uls' => 290,
      'mml' => 291,
      'txt' => 292,
      'pot' => 292,
      'text' => 292,
      'asc' => 292,
      'rtx' => 293,
      'wsc' => 294,
      'sct' => 294,
      'tsv' => 295,
      'ts' => 296,
      'tm' => 296,
      'jad' => 297,
      'wml' => 298,
      'wmls' => 299,
      'bib' => 300,
      'boo' => 301,
      'hpp' => 302,
      'hh' => 302,
      'h++' => 302,
      'hxx' => 302,
      'cxx' => 303,
      'cc' => 303,
      'cpp' => 303,
      'c++' => 303,
      'h' => 304,
      'htc' => 305,
      'csh' => 306,
      'c' => 307,
      'patch' => 308,
      'diff' => 308,
      'd' => 309,
      'hs' => 310,
      'java' => 311,
      'lhs' => 312,
      'moc' => 313,
      'pas' => 314,
      'p' => 314,
      'gcd' => 315,
      'pm' => 316,
      'pl' => 316,
      'py' => 317,
      'etx' => 318,
      'sh' => 319,
      'tk' => 320,
      'tcl' => 320,
      'cls' => 321,
      'ltx' => 321,
      'sty' => 321,
      'tex' => 321,
      'vcs' => 322,
      'vcf' => 323,
      '3gp' => 324,
      'dl' => 325,
      'dif' => 326,
      'dv' => 326,
      'fli' => 327,
      'gl' => 328,
      'mp4' => 329,
      'f4v' => 329,
      'f4p' => 329,
      'mpe' => 330,
      'mpeg' => 330,
      'mpg' => 330,
      'ogv' => 331,
      'qt' => 332,
      'mov' => 332,
      'mxu' => 333,
      'lsf' => 334,
      'lsx' => 334,
      'mng' => 335,
      'asx' => 336,
      'asf' => 336,
      'wm' => 337,
      'wmv' => 338,
      'wmx' => 339,
      'wvx' => 340,
      'avi' => 341,
      'movie' => 342,
      'ice' => 343,
      'sisx' => 344,
      'wrl' => 345,
      'vrm' => 345,
      'vrml' => 345,
      'f4a' => 346,
      'f4b' => 346,
      'm4a' => 346,
      'flv' => 347,
      'm4v' => 348,
      'azw' => 349,
      'epub' => 350,
      'mobi' => 351,
      'prc' => 352,
      'mkv' => 353,
      'mka' => 354,
      'webp' => 355,
      'weba' => 356,
      'webm' => 357,
      'vtt' => 358,
      'gz' => 359,
      'mjs' => 132,
      'aac' => 360,
      'json' => 361,
    ],
  ];

  /**
   * The MIME types mapping array after going through the module handler.
   *
   * @var array
   */
  protected $mapping;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a new ExtensionMimeTypeGuesser.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function guessMimeType($path): ?string {
    if ($this->mapping === NULL) {
      $mapping = $this->defaultMapping;
      // Allow modules to alter the default mapping.
      $this->moduleHandler->alter('file_mimetype_mapping', $mapping);
      $this->mapping = $mapping;
    }

    $extension = '';
    $file_parts = explode('.', \Drupal::service('file_system')->basename($path));

    // Remove the first part: a full filename should not match an extension.
    array_shift($file_parts);

    // Iterate over the file parts, trying to find a match.
    // For my.awesome.image.jpeg, we try:
    // - jpeg
    // - image.jpeg, and
    // - awesome.image.jpeg
    while ($additional_part = array_pop($file_parts)) {
      $extension = strtolower($additional_part . ($extension ? '.' . $extension : ''));
      if (isset($this->mapping['extensions'][$extension])) {
        return $this->mapping['mimetypes'][$this->mapping['extensions'][$extension]];
      }
    }

    return 'application/octet-stream';
  }

  /**
   * Sets the mimetypes/extension mapping to use when guessing mimetype.
   *
   * @param array|null $mapping
   *   Passing a NULL mapping will cause guess() to use self::$defaultMapping.
   */
  public function setMapping(array $mapping = NULL) {
    $this->mapping = $mapping;
  }

  /**
   * {@inheritdoc}
   */
  public function isGuesserSupported(): bool {
    return TRUE;
  }

}
