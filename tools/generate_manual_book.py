from reportlab.lib import colors
from reportlab.lib.enums import TA_CENTER
from reportlab.lib.pagesizes import A4
from reportlab.lib.styles import ParagraphStyle, getSampleStyleSheet
from reportlab.lib.units import cm
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
from reportlab.platypus import BaseDocTemplate, Frame, PageBreak, Paragraph, Spacer, Table, TableStyle

OUTPUT = 'output/pdf/Buku_Panduan_POS_Toko_Iwan_Jamu.pdf'

pdfmetrics.registerFont(TTFont('Arial', r'C:\Windows\Fonts\arial.ttf'))
pdfmetrics.registerFont(TTFont('Arial-Bold', r'C:\Windows\Fonts\arialbd.ttf'))

NAVY = colors.HexColor('#10261d')
GREEN = colors.HexColor('#28b56f')
PALE_GREEN = colors.HexColor('#e9f9f0')
YELLOW = colors.HexColor('#f3b241')
RED = colors.HexColor('#e85b5b')
INK = colors.HexColor('#182235')
MUTED = colors.HexColor('#607087')
LINE = colors.HexColor('#dbe3ea')

styles = getSampleStyleSheet()
styles.add(ParagraphStyle(name='CoverTitle', fontName='Arial-Bold', fontSize=30, leading=36, textColor=colors.white, alignment=TA_CENTER, spaceAfter=12))
styles.add(ParagraphStyle(name='CoverSub', fontName='Arial', fontSize=13, leading=19, textColor=colors.HexColor('#d6f6e4'), alignment=TA_CENTER))
styles.add(ParagraphStyle(name='H1x', fontName='Arial-Bold', fontSize=20, leading=25, textColor=NAVY, spaceBefore=3, spaceAfter=10))
styles.add(ParagraphStyle(name='H2x', fontName='Arial-Bold', fontSize=13, leading=18, textColor=GREEN, spaceBefore=12, spaceAfter=5))
styles.add(ParagraphStyle(name='Bodyx', fontName='Arial', fontSize=9.4, leading=14, textColor=INK, spaceAfter=6))
styles.add(ParagraphStyle(name='Smallx', fontName='Arial', fontSize=8.2, leading=11, textColor=MUTED))
styles.add(ParagraphStyle(name='Callout', fontName='Arial', fontSize=9.2, leading=14, textColor=INK, backColor=PALE_GREEN, borderColor=colors.HexColor('#bcebd0'), borderWidth=.6, borderPadding=9, spaceBefore=5, spaceAfter=10))

def p(text, style='Bodyx'):
    return Paragraph(text, styles[style])

def bullet(text):
    return p('&bull; ' + text)

def section(title, intro=None):
    out = [p(title, 'H1x')]
    if intro:
        out.append(p(intro))
    return out

def table(headers, rows, widths=None):
    data = [[p(h, 'Smallx') for h in headers]] + [[p(str(cell), 'Smallx') for cell in row] for row in rows]
    t = Table(data, colWidths=widths, repeatRows=1, hAlign='LEFT')
    t.setStyle(TableStyle([
        ('BACKGROUND', (0,0), (-1,0), NAVY), ('TEXTCOLOR', (0,0), (-1,0), colors.white),
        ('FONTNAME', (0,0), (-1,0), 'Arial-Bold'), ('VALIGN', (0,0), (-1,-1), 'TOP'),
        ('GRID', (0,0), (-1,-1), .35, LINE), ('ROWBACKGROUNDS', (0,1), (-1,-1), [colors.white, colors.HexColor('#f8fafc')]),
        ('LEFTPADDING', (0,0), (-1,-1), 7), ('RIGHTPADDING', (0,0), (-1,-1), 7),
        ('TOPPADDING', (0,0), (-1,-1), 6), ('BOTTOMPADDING', (0,0), (-1,-1), 6),
    ]))
    return t

def footer(canvas, doc):
    canvas.saveState()
    canvas.setStrokeColor(LINE)
    canvas.line(1.7*cm, 1.35*cm, A4[0]-1.7*cm, 1.35*cm)
    canvas.setFont('Arial', 8)
    canvas.setFillColor(MUTED)
    canvas.drawString(1.7*cm, .85*cm, 'POS Toko Iwan Jamu - Buku Panduan Pengguna')
    canvas.drawRightString(A4[0]-1.7*cm, .85*cm, 'Halaman %d' % doc.page)
    canvas.restoreState()

doc = BaseDocTemplate(OUTPUT, pagesize=A4, leftMargin=1.7*cm, rightMargin=1.7*cm, topMargin=1.55*cm, bottomMargin=1.75*cm)
doc.addPageTemplates([__import__('reportlab.platypus', fromlist=['PageTemplate']).PageTemplate(id='main', frames=[Frame(doc.leftMargin, doc.bottomMargin, doc.width, doc.height, id='body')], onPage=footer)])
story = []

cover = Table([[p('BUKU PANDUAN<br/>POS TOKO IWAN JAMU', 'CoverTitle')], [p('Panduan operasional untuk Admin, Kasir, dan Gudang<br/>Toko A - Toko B - Gudang', 'CoverSub')]], colWidths=[17.6*cm], rowHeights=[5.5*cm, 2.4*cm])
cover.setStyle(TableStyle([('BACKGROUND',(0,0),(-1,-1),NAVY),('VALIGN',(0,0),(-1,-1),'MIDDLE'),('BOX',(0,0),(-1,-1),0,NAVY),('LEFTPADDING',(0,0),(-1,-1),20),('RIGHTPADDING',(0,0),(-1,-1),20)]))
story += [Spacer(1, 5.0*cm), cover, Spacer(1, 1*cm), p('Versi panduan: Agustus 2026', 'Smallx'), PageBreak()]

story += section('Daftar Isi', 'Gunakan bagian yang sesuai dengan peran Anda. Menu yang tampil mengikuti hak akses akun.')
for item in ['1. Memulai dan keamanan akun', '2. Peran pengguna dan lokasi kerja', '3. Dashboard, notifikasi, dan tindakan cepat', '4. Kasir dan transaksi penjualan', '5. Produk, pembelian, dan persediaan', '6. Transfer Gudang ke Toko', '7. Keuangan dan laporan', '8. Pengaturan lanjutan dan pemecahan masalah']:
    story.append(p(item, 'H2x'))
story += [Spacer(1, 8), p('Alamat aplikasi: <b>https://app.tokoiwanjamu.my.id</b>', 'Callout'), PageBreak()]

story += section('1. Memulai dan Keamanan Akun', 'Setiap pengguna masuk menggunakan akun masing-masing. Jangan membagikan kata sandi atau memakai akun rekan kerja.')
story += [p('Langkah masuk', 'H2x'), bullet('Buka alamat aplikasi, masukkan email/username dan kata sandi Anda.'), bullet('Setelah berhasil masuk, periksa nama lokasi aktif di bagian profil/menu.'), bullet('Gunakan tombol keluar setelah selesai, terutama pada perangkat bersama.'), p('Keamanan penting', 'H2x'), bullet('Admin membuat dan mengubah akun melalui menu Akun Pengguna. Kasir dan Gudang tidak dapat mengubah data pengguna.'), bullet('Bila akun salah lokasi atau tidak dapat mengakses menu, hubungi Admin. Jangan menggunakan akun Admin untuk pekerjaan harian.'), p('Pada HP atau tablet', 'H2x'), bullet('Tekan tombol menu untuk membuka sidebar. Sidebar menutup kembali agar area kerja lebih luas.'), bullet('Tabel dapat digeser ke samping bila kolom tidak muat. Gunakan mode layar tegak untuk kasir dan daftar.')]
story.append(PageBreak())

story += section('2. Peran Pengguna dan Lokasi Kerja', 'Sistem memisahkan data menurut lokasi: Gudang, Toko A, dan Toko B.')
story.append(table(['Peran', 'Tugas utama', 'Batasan'], [
    ['Admin', 'Melihat seluruh lokasi, mengelola data, akun, izin, laporan, dan dapat berpindah tampilan/lokasi.', 'Tetap pilih lokasi aktif sebelum mengerjakan proses yang khusus Gudang atau Toko.'],
    ['Gudang', 'Pembelian, pengelolaan stok Gudang, dan mengirim transfer ke Toko.', 'Tidak menerima transfer sebagai Toko.'],
    ['Kasir Toko A/B', 'Penjualan, shift, stok lokasi sendiri, dan menerima transfer yang dikirim ke tokonya.', 'Tidak dapat melihat atau mengubah data lokasi lain.'],
], [3*cm, 7.1*cm, 7.5*cm]))
story += [p('Berpindah lokasi untuk Admin', 'H2x'), bullet('Klik kartu profil Admin atau pilihan lokasi yang tersedia di sidebar.'), bullet('Pilih Gudang untuk membuat transfer keluar; pilih Toko A/B untuk mengecek penerimaan transfer dan aktivitas toko.'), p('Catatan', 'Callout'), p('Data stok, transaksi, kas, dan notifikasi selalu terkait lokasi. Pastikan lokasi aktif benar sebelum menyimpan data.')]
story.append(PageBreak())

story += section('3. Dashboard, Notifikasi, dan Tindakan Cepat', 'Dashboard dibuat ringkas untuk membantu keputusan harian.')
story += [bullet('Kartu lokasi menampilkan ringkasan tiap Toko dan Gudang untuk Admin.'), bullet('Notifikasi Aktivitas dapat diklik untuk melihat transaksi atau perubahan stok terkait.'), bullet('Tindakan cepat Transaksi Baru membuka kasir.'), bullet('Saat lokasi aktif adalah Gudang, tindakan cepat Kirim Transfer muncul.'), bullet('Saat lokasi aktif adalah Toko, tindakan cepat Penerimaan Transfer menunjukkan jumlah yang menunggu.')]
story += [p('Arti warna status', 'H2x'), table(['Warna', 'Arti', 'Contoh'], [['Hijau', 'Selesai / aman', 'Transfer diterima, stok aman'], ['Kuning', 'Menunggu tindak lanjut', 'Transfer dikirim, menunggu Toko menerima'], ['Merah', 'Masalah / perlu perhatian', 'Stok menipis atau peringatan kedaluwarsa']], [3*cm, 6.5*cm, 8.1*cm]), p('Kebiasaan yang dianjurkan', 'H2x'), bullet('Periksa notifikasi dan stok menipis pada awal shift.'), bullet('Klik angka atau kartu aktivitas bila ingin melihat rincian, bukan hanya total.')]
story.append(PageBreak())

story += section('4. Kasir dan Transaksi Penjualan', 'Menu Kasir digunakan untuk mencatat penjualan langsung kepada pelanggan.')
story += [p('Proses transaksi', 'H2x'), bullet('Buka Kasir atau klik Transaksi Baru dari Dashboard.'), bullet('Cari/scan produk, pilih kemasan bila tersedia, lalu tentukan jumlah.'), bullet('Pastikan harga, jumlah, diskon/promo, dan total telah benar.'), bullet('Pilih metode pembayaran dan masukkan nominal bayar bila tunai.'), bullet('Simpan transaksi. Cetak atau tampilkan struk bila diperlukan.'), p('Shift kasir', 'H2x'), bullet('Buka shift sebelum mulai menerima uang tunai.'), bullet('Saat selesai, tutup shift dan cocokkan uang fisik dengan nilai yang ditampilkan sistem.'), p('Retur penjualan', 'H2x'), bullet('Gunakan Retur Penjualan untuk pembatalan/retur yang sah. Jangan menghapus transaksi lama agar riwayat audit tetap ada.')]
story.append(PageBreak())

story += section('5. Produk, Pembelian, dan Persediaan', 'Bagian Persediaan membantu memastikan stok dan harga tercatat konsisten.')
story += [p('Produk dan master data', 'H2x'), bullet('Admin mengelola Master Barang, kategori, satuan, harga beli/jual, stok minimum, dan kemasan.'), bullet('Gunakan kode produk yang unik. Kemasan seperti karton dapat memiliki konversi ke satuan dasar.'), p('Pembelian dan stok', 'H2x'), bullet('Catat pembelian pada lokasi yang menerima barang. Pembelian menaikkan stok dan dapat membuat hutang supplier.'), bullet('Kartu Stok dipakai untuk melihat riwayat masuk/keluar. Stok Opname digunakan saat hasil fisik berbeda dari sistem.'), bullet('Batch dan kedaluwarsa membantu memantau barang yang memiliki tanggal habis.'), p('Aturan data', 'Callout'), p('Jangan mengurangi stok dengan mengedit angka secara sembarangan. Gunakan pembelian, transfer, pengeluaran barang, retur, atau opname agar catatan stok tetap dapat diaudit.')]
story.append(PageBreak())

story += section('6. Transfer Gudang ke Toko', 'Transfer memiliki dua status agar stok tidak dianggap tersedia di toko sebelum barang benar-benar diterima.')
story.append(table(['Tahap', 'Pelaksana', 'Hasil'], [
    ['1. Dikirim', 'Gudang / Admin saat lokasi Gudang aktif', 'Stok Gudang berkurang. Transfer berstatus kuning Dikirim. Stok Toko belum bertambah.'],
    ['2. Diterima', 'Kasir Toko tujuan / Admin saat lokasi Toko aktif', 'Petugas memeriksa barang, lalu menekan Terima. Stok Toko bertambah dan status menjadi hijau Diterima.'],
], [3*cm, 5.3*cm, 9.3*cm]))
story += [p('Membuat transfer dari Gudang', 'H2x'), bullet('Pilih lokasi Gudang, buka Transfer Stok.'), bullet('Pilih tujuan Toko A atau Toko B, tambahkan barang, kemasan, jumlah, dan catatan pengiriman.'), bullet('Periksa stok Gudang lalu tekan Kirim Transfer.'), p('Menerima transfer di Toko', 'H2x'), bullet('Pilih lokasi Toko tujuan, buka Penerimaan Transfer.'), bullet('Buka detail nomor transfer dan cocokkan nama barang, kemasan, serta jumlah fisik.'), bullet('Tekan Terima Transfer hanya setelah barang benar-benar diterima. Tindakan ini menaikkan stok Toko.'), p('Jika ada selisih', 'Callout'), p('Jangan menekan Terima bila jumlah fisik tidak sesuai. Catat selisih dan hubungi Admin/Gudang untuk pengecekan. Transfer harus diselesaikan berdasarkan barang fisik.')]
story.append(PageBreak())

story += section('7. Keuangan dan Laporan', 'Keuangan dicatat berdasarkan jenisnya agar modal pemilik tidak tercampur dengan kas operasional.')
story += [p('Keuangan operasional', 'H2x'), bullet('Kas mencatat pemasukan/pengeluaran operasional di lokasi terkait.'), bullet('Hutang Supplier mencatat tagihan pembelian yang belum lunas dan pembayaran supplier.'), bullet('Modal Pemilik mencatat setoran atau penarikan modal. Ini berbeda dari uang buka/tutup shift kasir.'), p('Laporan', 'H2x'), bullet('Laporan Penjualan: transaksi, omzet, dan ekspor data.'), bullet('Laporan Pembelian: penerimaan barang dan pembelian supplier.'), bullet('Laporan Stok: jumlah serta nilai stok per lokasi.'), bullet('Laporan Keuntungan: ringkasan laba berdasarkan penjualan dan biaya terkait.'), bullet('Laporan Retur dan Arus Kas: pantau koreksi transaksi serta pergerakan kas.'), p('Saran kontrol', 'Callout'), p('Tentukan rentang tanggal sebelum mengambil kesimpulan dari laporan. Gunakan ekspor untuk arsip atau pemeriksaan lanjutan.')]
story.append(PageBreak())

story += section('8. Pengaturan Lanjutan dan Pemecahan Masalah', 'Bagian ini terutama untuk Admin.')
story += [p('Pengaturan Lanjutan', 'H2x'), bullet('Akun Pengguna: membuat akun login dan menetapkan lokasi/peran.'), bullet('Role dan Hak Akses: mengatur menu/aksi yang boleh dibuka tiap peran.'), bullet('Manajemen Toko: mengelola Toko A, Toko B, dan Gudang.'), bullet('Pengaturan Menu: mengaktifkan/menonaktifkan menu sesuai kebutuhan.'), bullet('Audit Aktivitas: menelusuri perubahan penting yang dibuat pengguna.'), p('Masalah umum', 'H2x')]
story.append(table(['Masalah', 'Tindakan'], [
    ['Menu tidak terlihat', 'Periksa peran akun, izin, lokasi aktif, dan status menu. Admin dapat mengecek Akun Pengguna serta Role dan Hak Akses.'],
    ['Tidak bisa membuat transfer', 'Pastikan lokasi aktif adalah Gudang dan stok cukup.'],
    ['Tidak bisa menerima transfer', 'Pastikan lokasi aktif adalah Toko tujuan dan transfer masih berstatus Dikirim.'],
    ['Stok toko belum bertambah', 'Pastikan petugas Toko sudah menekan Terima Transfer.'],
    ['Tampilan di HP sempit', 'Tekan tombol menu untuk sidebar; geser tabel horizontal bila perlu dan putar perangkat bila membaca laporan lebar.'],
], [5.1*cm, 12.5*cm]))
story += [p('Penutup', 'H2x'), p('Sistem akan paling akurat bila setiap proses dicatat saat kejadian: transaksi saat menjual, pembelian saat barang datang, dan penerimaan transfer setelah barang tiba. Gunakan riwayat serta Audit Aktivitas bila perlu menelusuri perbedaan.')]

doc.build(story)
print(OUTPUT)
