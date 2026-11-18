<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SurahAlquranSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        // Data 114 Surah (Arab Gundul)
        $surahs = [
            ['nama_surah' => 'Al-Fatihah', 'nama_surah_arab' => 'الفاتحة', 'jumlah_ayat' => 7],
            ['nama_surah' => 'Al-Baqarah', 'nama_surah_arab' => 'البقرة', 'jumlah_ayat' => 286],
            ['nama_surah' => 'Ali \'Imran', 'nama_surah_arab' => 'آل عمران', 'jumlah_ayat' => 200],
            ['nama_surah' => 'An-Nisa\'', 'nama_surah_arab' => 'النساء', 'jumlah_ayat' => 176],
            ['nama_surah' => 'Al-Ma\'idah', 'nama_surah_arab' => 'المائدة', 'jumlah_ayat' => 120],
            ['nama_surah' => 'Al-An\'am', 'nama_surah_arab' => 'الأنعام', 'jumlah_ayat' => 165],
            ['nama_surah' => 'Al-A\'raf', 'nama_surah_arab' => 'الأعراف', 'jumlah_ayat' => 206],
            ['nama_surah' => 'Al-Anfal', 'nama_surah_arab' => 'الأنفال', 'jumlah_ayat' => 75],
            ['nama_surah' => 'At-Taubah', 'nama_surah_arab' => 'التوبة', 'jumlah_ayat' => 129],
            ['nama_surah' => 'Yunus', 'nama_surah_arab' => 'يونس', 'jumlah_ayat' => 109],
            ['nama_surah' => 'Hud', 'nama_surah_arab' => 'هود', 'jumlah_ayat' => 123],
            ['nama_surah' => 'Yusuf', 'nama_surah_arab' => 'يوسف', 'jumlah_ayat' => 111],
            ['nama_surah' => 'Ar-Ra\'d', 'nama_surah_arab' => 'الرعد', 'jumlah_ayat' => 43],
            ['nama_surah' => 'Ibrahim', 'nama_surah_arab' => 'إبراهيم', 'jumlah_ayat' => 52],
            ['nama_surah' => 'Al-Hijr', 'nama_surah_arab' => 'الحجر', 'jumlah_ayat' => 99],
            ['nama_surah' => 'An-Nahl', 'nama_surah_arab' => 'النحل', 'jumlah_ayat' => 128],
            ['nama_surah' => 'Al-Isra\'', 'nama_surah_arab' => 'الإسراء', 'jumlah_ayat' => 111],
            ['nama_surah' => 'Al-Kahf', 'nama_surah_arab' => 'الكهف', 'jumlah_ayat' => 110],
            ['nama_surah' => 'Maryam', 'nama_surah_arab' => 'مريم', 'jumlah_ayat' => 98],
            ['nama_surah' => 'Ta Ha', 'nama_surah_arab' => 'طه', 'jumlah_ayat' => 135],
            ['nama_surah' => 'Al-Anbiya\'', 'nama_surah_arab' => 'الأنبياء', 'jumlah_ayat' => 112],
            ['nama_surah' => 'Al-Hajj', 'nama_surah_arab' => 'الحج', 'jumlah_ayat' => 78],
            ['nama_surah' => 'Al-Mu\'minun', 'nama_surah_arab' => 'المؤمنون', 'jumlah_ayat' => 118],
            ['nama_surah' => 'An-Nur', 'nama_surah_arab' => 'النور', 'jumlah_ayat' => 64],
            ['nama_surah' => 'Al-Furqan', 'nama_surah_arab' => 'الفرقان', 'jumlah_ayat' => 77],
            ['nama_surah' => 'Asy-Syu\'ara\'', 'nama_surah_arab' => 'الشعراء', 'jumlah_ayat' => 227],
            ['nama_surah' => 'An-Naml', 'nama_surah_arab' => 'النمل', 'jumlah_ayat' => 93],
            ['nama_surah' => 'Al-Qasas', 'nama_surah_arab' => 'القصص', 'jumlah_ayat' => 88],
            ['nama_surah' => 'Al-\'Ankabut', 'nama_surah_arab' => 'العنكبوت', 'jumlah_ayat' => 69],
            ['nama_surah' => 'Ar-Rum', 'nama_surah_arab' => 'الروم', 'jumlah_ayat' => 60],
            ['nama_surah' => 'Luqman', 'nama_surah_arab' => 'لقمان', 'jumlah_ayat' => 34],
            ['nama_surah' => 'As-Sajdah', 'nama_surah_arab' => 'السجدة', 'jumlah_ayat' => 30],
            ['nama_surah' => 'Al-Ahzab', 'nama_surah_arab' => 'الأحزاب', 'jumlah_ayat' => 73],
            ['nama_surah' => 'Saba\'', 'nama_surah_arab' => 'سبأ', 'jumlah_ayat' => 54],
            ['nama_surah' => 'Fatir', 'nama_surah_arab' => 'فاطر', 'jumlah_ayat' => 45],
            ['nama_surah' => 'Ya Sin', 'nama_surah_arab' => 'يس', 'jumlah_ayat' => 83],
            ['nama_surah' => 'As-Saffat', 'nama_surah_arab' => 'الصافات', 'jumlah_ayat' => 182],
            ['nama_surah' => 'Sad', 'nama_surah_arab' => 'ص', 'jumlah_ayat' => 88],
            ['nama_surah' => 'Az-Zumar', 'nama_surah_arab' => 'الزمر', 'jumlah_ayat' => 75],
            ['nama_surah' => 'Gafir', 'nama_surah_arab' => 'غافر', 'jumlah_ayat' => 85],
            ['nama_surah' => 'Fussilat', 'nama_surah_arab' => 'فصلت', 'jumlah_ayat' => 54],
            ['nama_surah' => 'Asy-Syura', 'nama_surah_arab' => 'الشورى', 'jumlah_ayat' => 53],
            ['nama_surah' => 'Az-Zukhruf', 'nama_surah_arab' => 'الزخرف', 'jumlah_ayat' => 89],
            ['nama_surah' => 'Ad-Dukhan', 'nama_surah_arab' => 'الدخان', 'jumlah_ayat' => 59],
            ['nama_surah' => 'Al-Jasiyah', 'nama_surah_arab' => 'الجاثية', 'jumlah_ayat' => 37],
            ['nama_surah' => 'Al-Ahqaf', 'nama_surah_arab' => 'الأحقاف', 'jumlah_ayat' => 35],
            ['nama_surah' => 'Muhammad', 'nama_surah_arab' => 'محمد', 'jumlah_ayat' => 38],
            ['nama_surah' => 'Al-Fath', 'nama_surah_arab' => 'الفتح', 'jumlah_ayat' => 29],
            ['nama_surah' => 'Al-Hujurat', 'nama_surah_arab' => 'الحجرات', 'jumlah_ayat' => 18],
            ['nama_surah' => 'Qaf', 'nama_surah_arab' => 'ق', 'jumlah_ayat' => 45],
            ['nama_surah' => 'Az-Zariyat', 'nama_surah_arab' => 'الذاريات', 'jumlah_ayat' => 60],
            ['nama_surah' => 'At-Tur', 'nama_surah_arab' => 'الطور', 'jumlah_ayat' => 49],
            ['nama_surah' => 'An-Najm', 'nama_surah_arab' => 'النجم', 'jumlah_ayat' => 62],
            ['nama_surah' => 'Al-Qamar', 'nama_surah_arab' => 'القمر', 'jumlah_ayat' => 55],
            ['nama_surah' => 'Ar-Rahman', 'nama_surah_arab' => 'الرحمن', 'jumlah_ayat' => 78],
            ['nama_surah' => 'Al-Waqi\'ah', 'nama_surah_arab' => 'الواقعة', 'jumlah_ayat' => 96],
            ['nama_surah' => 'Al-Hadid', 'nama_surah_arab' => 'الحديد', 'jumlah_ayat' => 29],
            ['nama_surah' => 'Al-Mujadalah', 'nama_surah_arab' => 'المجادلة', 'jumlah_ayat' => 22],
            ['nama_surah' => 'Al-Hasyr', 'nama_surah_arab' => 'الحشر', 'jumlah_ayat' => 24],
            ['nama_surah' => 'Al-Mumtahanah', 'nama_surah_arab' => 'الممتحنة', 'jumlah_ayat' => 13],
            ['nama_surah' => 'As-Saff', 'nama_surah_arab' => 'الصف', 'jumlah_ayat' => 14],
            ['nama_surah' => 'Al-Jumu\'ah', 'nama_surah_arab' => 'الجمعة', 'jumlah_ayat' => 11],
            ['nama_surah' => 'Al-Munafiqun', 'nama_surah_arab' => 'المنافقون', 'jumlah_ayat' => 11],
            ['nama_surah' => 'At-Tagabun', 'nama_surah_arab' => 'التغابن', 'jumlah_ayat' => 18],
            ['nama_surah' => 'At-Talaq', 'nama_surah_arab' => 'الطلاق', 'jumlah_ayat' => 12],
            ['nama_surah' => 'At-Tahrim', 'nama_surah_arab' => 'التحريم', 'jumlah_ayat' => 12],
            ['nama_surah' => 'Al-Mulk', 'nama_surah_arab' => 'الملك', 'jumlah_ayat' => 30],
            ['nama_surah' => 'Al-Qalam', 'nama_surah_arab' => 'القلم', 'jumlah_ayat' => 52],
            ['nama_surah' => 'Al-Haqqah', 'nama_surah_arab' => 'الحاقة', 'jumlah_ayat' => 52],
            ['nama_surah' => 'Al-Ma\'arij', 'nama_surah_arab' => 'المعارج', 'jumlah_ayat' => 44],
            ['nama_surah' => 'Nuh', 'nama_surah_arab' => 'نوح', 'jumlah_ayat' => 28],
            ['nama_surah' => 'Al-Jinn', 'nama_surah_arab' => 'الجن', 'jumlah_ayat' => 28],
            ['nama_surah' => 'Al-Muzzammil', 'nama_surah_arab' => 'المزمل', 'jumlah_ayat' => 20],
            ['nama_surah' => 'Al-Muddassir', 'nama_surah_arab' => 'المدثر', 'jumlah_ayat' => 56],
            ['nama_surah' => 'Al-Qiyamah', 'nama_surah_arab' => 'القيامة', 'jumlah_ayat' => 40],
            ['nama_surah' => 'Al-Insan', 'nama_surah_arab' => 'الإنسان', 'jumlah_ayat' => 31],
            ['nama_surah' => 'Al-Mursalat', 'nama_surah_arab' => 'المرسلات', 'jumlah_ayat' => 50],
            ['nama_surah' => 'An-Naba\'', 'nama_surah_arab' => 'النبأ', 'jumlah_ayat' => 40],
            ['nama_surah' => 'An-Nazi\'at', 'nama_surah_arab' => 'النازعات', 'jumlah_ayat' => 46],
            ['nama_surah' => '\'Abasa', 'nama_surah_arab' => 'عبس', 'jumlah_ayat' => 42],
            ['nama_surah' => 'At-Takwir', 'nama_surah_arab' => 'التكوير', 'jumlah_ayat' => 29],
            ['nama_surah' => 'Al-Infitar', 'nama_surah_arab' => 'الانفطار', 'jumlah_ayat' => 19],
            ['nama_surah' => 'Al-Mutaffifin', 'nama_surah_arab' => 'المطففين', 'jumlah_ayat' => 36],
            ['nama_surah' => 'Al-Insyiqaq', 'nama_surah_arab' => 'الانشقاق', 'jumlah_ayat' => 25],
            ['nama_surah' => 'Al-Buruj', 'nama_surah_arab' => 'البروج', 'jumlah_ayat' => 22],
            ['nama_surah' => 'At-Tariq', 'nama_surah_arab' => 'الطارق', 'jumlah_ayat' => 17],
            ['nama_surah' => 'Al-A\'la', 'nama_surah_arab' => 'الأعلى', 'jumlah_ayat' => 19],
            ['nama_surah' => 'Al-Gasyiyah', 'nama_surah_arab' => 'الغاشية', 'jumlah_ayat' => 26],
            ['nama_surah' => 'Al-Fajr', 'nama_surah_arab' => 'الفجر', 'jumlah_ayat' => 30],
            ['nama_surah' => 'Al-Balad', 'nama_surah_arab' => 'البلد', 'jumlah_ayat' => 20],
            ['nama_surah' => 'Asy-Syams', 'nama_surah_arab' => 'الشمس', 'jumlah_ayat' => 15],
            ['nama_surah' => 'Al-Lail', 'nama_surah_arab' => 'الليل', 'jumlah_ayat' => 21],
            ['nama_surah' => 'Ad-Duha', 'nama_surah_arab' => 'الضحى', 'jumlah_ayat' => 11],
            ['nama_surah' => 'Asy-Syarh', 'nama_surah_arab' => 'الشرح', 'jumlah_ayat' => 8],
            ['nama_surah' => 'At-Tin', 'nama_surah_arab' => 'التين', 'jumlah_ayat' => 8],
            ['nama_surah' => 'Al-\'Alaq', 'nama_surah_arab' => 'العلق', 'jumlah_ayat' => 19],
            ['nama_surah' => 'Al-Qadr', 'nama_surah_arab' => 'القدر', 'jumlah_ayat' => 5],
            ['nama_surah' => 'Al-Bayyinah', 'nama_surah_arab' => 'البينة', 'jumlah_ayat' => 8],
            ['nama_surah' => 'Az-Zalzalah', 'nama_surah_arab' => 'الزلزلة', 'jumlah_ayat' => 8],
            ['nama_surah' => 'Al-\'Adiyat', 'nama_surah_arab' => 'العاديات', 'jumlah_ayat' => 11],
            ['nama_surah' => 'Al-Qari\'ah', 'nama_surah_arab' => 'القارعة', 'jumlah_ayat' => 11],
            ['nama_surah' => 'At-Takasur', 'nama_surah_arab' => 'التكاثر', 'jumlah_ayat' => 8],
            ['nama_surah' => 'Al-\'Asr', 'nama_surah_arab' => 'العصر', 'jumlah_ayat' => 3],
            ['nama_surah' => 'Al-Humazah', 'nama_surah_arab' => 'الهمزة', 'jumlah_ayat' => 9],
            ['nama_surah' => 'Al-Fil', 'nama_surah_arab' => 'الفيل', 'jumlah_ayat' => 5],
            ['nama_surah' => 'Quraisy', 'nama_surah_arab' => 'قريش', 'jumlah_ayat' => 4],
            ['nama_surah' => 'Al-Ma\'un', 'nama_surah_arab' => 'الماعون', 'jumlah_ayat' => 7],
            ['nama_surah' => 'Al-Kausar', 'nama_surah_arab' => 'الكوثر', 'jumlah_ayat' => 3],
            ['nama_surah' => 'Al-Kafirun', 'nama_surah_arab' => 'الكافرون', 'jumlah_ayat' => 6],
            ['nama_surah' => 'An-Nasr', 'nama_surah_arab' => 'النصر', 'jumlah_ayat' => 3],
            ['nama_surah' => 'Al-Lahab', 'nama_surah_arab' => 'المسد', 'jumlah_ayat' => 5],
            ['nama_surah' => 'Al-Ikhlas', 'nama_surah_arab' => 'الإخلاص', 'jumlah_ayat' => 4],
            ['nama_surah' => 'Al-Falaq', 'nama_surah_arab' => 'الفلق', 'jumlah_ayat' => 5],
            ['nama_surah' => 'An-Nas', 'nama_surah_arab' => 'الناس', 'jumlah_ayat' => 6],
        ];

        $surahData = array_map(function ($surah) use ($now) {
            return array_merge($surah, [
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }, $surahs);

        DB::table('akademik_master_t2q_surah')->insert($surahData);
    }
}