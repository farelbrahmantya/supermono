<?php defined('BASEPATH') OR exit('No direct script access allowed');
// Logo LASMONO: ilustrasi Pak Lasmono (avatar flat) di lencana emas Samudera.
// Pemakaian: $this->load->view('partials/logo', array('size' => 44));
$size = isset($size) ? (int) $size : 44;
?>
<svg xmlns="http://www.w3.org/2000/svg" class="lx-avatar" width="<?= $size; ?>" height="<?= $size; ?>" viewBox="0 0 120 120" role="img" aria-label="Logo LASMONO">
    <defs><clipPath id="lxAvatarClip"><circle cx="60" cy="60" r="60"/></clipPath></defs>
    <g clip-path="url(#lxAvatarClip)">
        <!-- Lencana emas + ombak -->
        <rect width="120" height="120" fill="#f3b93b"/>
        <path d="M-10 74 Q5 66 20 74 T50 74 T80 74 T110 74 T140 74 V120 H-10Z" fill="#eaa823" opacity=".6"/>
        <path d="M-10 86 Q5 78 20 86 T50 86 T80 86 T110 86 T140 86 V120 H-10Z" fill="#df9a1b" opacity=".55"/>

        <!-- Jas navy -->
        <path d="M10 122 C12 98 28 88 46 84 L60 97 L74 84 C92 88 108 98 110 122Z" fill="#1d2b4f"/>
        <!-- Kemeja putih -->
        <path d="M47 84 L60 108 L73 84 L67 79 L60 87 L53 79Z" fill="#ffffff"/>
        <!-- Dasi biru -->
        <path d="M56.6 87.5 L63.4 87.5 L62 91.5 L65 110 L60 116 L55 110 L58 91.5Z" fill="#2f55b4"/>
        <path d="M56.6 87.5 L63.4 87.5 L62 91.5 L58 91.5Z" fill="#22408a"/>
        <!-- Kerah jas -->
        <path d="M46 84 L60 108 L52 96 L44 100 L39 88Z" fill="#15203b"/>
        <path d="M74 84 L60 108 L68 96 L76 100 L81 88Z" fill="#15203b"/>

        <!-- Leher -->
        <path d="M50.5 68 L69.5 68 L69.5 83 L60 89 L50.5 83Z" fill="#b8784c"/>
        <!-- Telinga -->
        <ellipse cx="36.5" cy="52" rx="4.6" ry="7" fill="#c78757"/>
        <ellipse cx="83.5" cy="52" rx="4.6" ry="7" fill="#c78757"/>
        <!-- Wajah (agak bulat) -->
        <path d="M37.5 46 C37.5 30 47 22 60 22 C73 22 82.5 30 82.5 46 C82.5 63 75 75.5 60 77.5 C45 75.5 37.5 63 37.5 46Z" fill="#d49b69"/>
        <!-- Rambut hitam pendek, pelipis sedikit mundur -->
        <path d="M36.8 47 C35 29 44.5 16.5 60 16.5 C75.5 16.5 85 29 83.2 47 C82 41 80.6 36.5 78 33.5 C76 31 73.5 30.6 71 31.4 C67 29.4 63.5 29 60 29 C56.5 29 53 29.4 49 31.4 C46.5 30.6 44 31 42 33.5 C39.4 36.5 38 41 36.8 47Z" fill="#17151a"/>
        <path d="M45 30.5 C49 25 54 23 60 23 C66 23 71 25 75 30.5 C70 28 65.5 27.4 60 27.4 C54.5 27.4 50 28 45 30.5Z" fill="#2c272e"/>

        <!-- Alis tebal -->
        <path d="M44.5 41.5 Q49.5 38.6 55 40.6" stroke="#17151a" stroke-width="2.8" fill="none" stroke-linecap="round"/>
        <path d="M65 40.6 Q70.5 38.6 75.5 41.5" stroke="#17151a" stroke-width="2.8" fill="none" stroke-linecap="round"/>
        <!-- Mata terbuka -->
        <ellipse cx="50" cy="47.9" rx="4" ry="2.5" fill="#ffffff"/>
        <ellipse cx="70" cy="47.9" rx="4" ry="2.5" fill="#ffffff"/>
        <circle cx="50.3" cy="48" r="2.15" fill="#3a2418"/>
        <circle cx="69.7" cy="48" r="2.15" fill="#3a2418"/>
        <circle cx="51.1" cy="47" r=".8" fill="#ffffff"/>
        <circle cx="70.3" cy="47" r=".8" fill="#ffffff"/>
        <path d="M45.6 47 Q50 44.2 54.4 47" stroke="#2a1a12" stroke-width="1.5" fill="none" stroke-linecap="round"/>
        <path d="M65.6 47 Q70 44.2 74.4 47" stroke="#2a1a12" stroke-width="1.5" fill="none" stroke-linecap="round"/>
        <!-- Kerutan senyum di bawah mata -->
        <path d="M46.5 51.4 Q50 52.8 53.5 51.4" stroke="#b07548" stroke-width="1" fill="none" opacity=".6" stroke-linecap="round"/>
        <path d="M66.5 51.4 Q70 52.8 73.5 51.4" stroke="#b07548" stroke-width="1" fill="none" opacity=".6" stroke-linecap="round"/>
        <!-- Hidung -->
        <path d="M59.8 49 C59 54 57 57 56.4 58.6 Q60 60.6 63.6 58.6" stroke="#a5663b" stroke-width="1.8" fill="none" stroke-linecap="round"/>
        <!-- Pipi -->
        <ellipse cx="46" cy="57" rx="4.2" ry="2.4" fill="#e3876f" opacity=".3"/>
        <ellipse cx="74" cy="57" rx="4.2" ry="2.4" fill="#e3876f" opacity=".3"/>
        <!-- Brewok tipis sepanjang rahang -->
        <path d="M40.5 56 C43 68 50.5 75.5 60 77.5 C69.5 75.5 77 68 79.5 56 C77 64.5 70.5 70.5 60 71.8 C49.5 70.5 43 64.5 40.5 56Z" fill="#2b211c" opacity=".28"/>
        <!-- Kumis -->
        <path d="M51 63.2 C53.5 60.4 57.2 60.4 60 61.8 C62.8 60.4 66.5 60.4 69 63.2 C66.2 62.6 63 63.2 60 64 C57 63.2 53.8 62.6 51 63.2Z" fill="#17151a"/>
        <!-- Garis jenggot menyambung kumis -->
        <path d="M51.6 63.6 C51.2 67 52.4 70.6 55 72.6" stroke="#17151a" stroke-width="1.5" fill="none" opacity=".7" stroke-linecap="round"/>
        <path d="M68.4 63.6 C68.8 67 67.6 70.6 65 72.6" stroke="#17151a" stroke-width="1.5" fill="none" opacity=".7" stroke-linecap="round"/>
        <!-- Senyum bibir tertutup -->
        <path d="M53.2 65.6 Q60 71 66.8 65.6" stroke="#8e3f37" stroke-width="2.2" fill="none" stroke-linecap="round"/>
        <!-- Janggut dagu -->
        <path d="M55 71.6 Q60 70 65 71.6 Q64.4 76.8 60 77.6 Q55.6 76.8 55 71.6Z" fill="#17151a"/>
    </g>
    <circle cx="60" cy="60" r="58.5" fill="none" stroke="#ffffff" stroke-opacity=".6" stroke-width="3"/>
</svg>
