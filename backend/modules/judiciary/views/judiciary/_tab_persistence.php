<?php
/**
 * تبويب كشف المثابرة — يُعرض عبر AJAX داخل الشاشة الموحدة
 * نفس محتوى cases_report.php لكن بدون breadcrumbs/title
 */
use yii\helpers\Url;

$dataUrl    = Url::to(['/judiciary/judiciary/cases-report-data']);
$exportUrl  = Url::to(['/judiciary/judiciary/export-cases-report']);
$printUrl   = Url::to(['/judiciary/judiciary/print-cases-report']);
$refreshUrl = Url::to(['/judiciary/judiciary/refresh-persistence-cache']);
?>

<div class="cr-page">
    <div class="cr-header">
        <h2><i class="fa fa-gavel"></i> كشف المثابره</h2>
        <div style="display:flex;gap:8px">
            <button type="button" class="cr-btn cr-btn-refresh" id="btnRefreshCache"><i class="fa fa-refresh"></i> تحديث</button>
            <button type="button" class="cr-btn cr-btn-export" id="btnExport"><i class="fa fa-file-excel-o"></i> تصدير Excel</button>
            <button type="button" class="cr-btn cr-btn-print" id="btnPrint"><i class="fa fa-print"></i> طباعة</button>
        </div>
    </div>
    <div class="cr-stats">
        <div class="cr-stat"><div class="cr-stat-icon total"><i class="fa fa-gavel"></i></div><div class="cr-stat-info"><p class="cr-stat-val" id="statTotal"><?= (int)$stats['total'] ?></p><p class="cr-stat-lbl">إجمالي القضايا</p></div></div>
        <div class="cr-stat"><div class="cr-stat-icon red"><i class="fa fa-exclamation-triangle"></i></div><div class="cr-stat-info"><p class="cr-stat-val" id="statRed"><?= (int)$stats['cnt_red'] ?></p><p class="cr-stat-lbl">بحاجة اهتمام عاجل</p></div></div>
        <div class="cr-stat"><div class="cr-stat-icon orange"><i class="fa fa-clock-o"></i></div><div class="cr-stat-info"><p class="cr-stat-val" id="statOrange"><?= (int)$stats['cnt_orange'] ?></p><p class="cr-stat-lbl">قريب من الاستحقاق</p></div></div>
        <div class="cr-stat"><div class="cr-stat-icon green"><i class="fa fa-check-circle"></i></div><div class="cr-stat-info"><p class="cr-stat-val" id="statGreen"><?= (int)$stats['cnt_green'] ?></p><p class="cr-stat-lbl">بحالة جيدة</p></div></div>
    </div>
    <div class="cr-tools">
        <input type="text" class="cr-search" id="crSearch" placeholder="ابحث بالاسم، المحكمة، رقم القضية أو العقد..." autocomplete="off">
        <button type="button" class="cr-filter-btn active" data-filter="all">الكل (<span class="filter-count"><?= (int)$stats['total'] ?></span>)</button>
        <button type="button" class="cr-filter-btn" data-filter="red">🔴 عاجل (<span class="filter-count"><?= (int)$stats['cnt_red'] ?></span>)</button>
        <button type="button" class="cr-filter-btn" data-filter="orange">🟠 قريب (<span class="filter-count"><?= (int)$stats['cnt_orange'] ?></span>)</button>
        <button type="button" class="cr-filter-btn" data-filter="green">🟢 جيد (<span class="filter-count"><?= (int)$stats['cnt_green'] ?></span>)</button>
    </div>
    <div class="cr-table-wrap" id="crTableWrap">
        <table class="cr-table" id="crTable">
            <thead><tr><th>#</th><th>رقم القضية</th><th>السنة</th><th>المحكمة</th><th>رقم العقد</th><th>اسم العميل</th><th>الإجراء الأخير</th><th>تاريخ آخر إجراء</th><th>مؤشّر المثابرة</th><th>آخر متابعة للعقد</th><th>آخر تشييك وظيفة</th><th>المحامي</th><th>الوظيفة</th><th>نوع الوظيفة</th></tr></thead>
            <tbody id="crBody"><tr><td colspan="14" class="cr-empty"><i class="fa fa-spinner fa-spin"></i>جارٍ تحميل البيانات...</td></tr></tbody>
        </table>
    </div>
    <div class="cr-pager" id="crPager"></div>
</div>

<script>
$('#lh-badge-persistence').text('<?= (int)$stats['total'] ?>');
(function($){
    var DATA_URL='<?= $dataUrl ?>', EXPORT_URL='<?= $exportUrl ?>', PRINT_URL='<?= $printUrl ?>', REFRESH_URL='<?= $refreshUrl ?>';
    var PER_PAGE=20, currentPage=1, showAll=false, activeFilter='all', searchQuery='', loading=false, xhr=null;
    function esc(s){if(!s&&s!==0)return'—';var d=document.createElement('div');d.appendChild(document.createTextNode(s));return d.innerHTML;}
    function fetchData(){if(loading){if(xhr)xhr.abort();}loading=true;showLoading();xhr=$.getJSON(DATA_URL,{page:currentPage,per_page:PER_PAGE,filter:activeFilter,search:searchQuery,show_all:showAll?'1':'0'},function(d){renderRows(d.rows,d.total,d.page,d.total_pages);updateStats(d.stats);buildPager(d.total,d.total_pages,d.page);loading=false;hideLoading();}).fail(function(j,t){if(t!=='abort'){$('#crBody').html('<tr><td colspan="14" class="cr-empty"><i class="fa fa-exclamation-circle"></i>خطأ</td></tr>');loading=false;hideLoading();}});}
    function showLoading(){if(!$('#crLoading').length)$('#crTableWrap').append('<div class="cr-loading" id="crLoading"><i class="fa fa-spinner"></i> جارٍ التحميل...</div>');}
    function hideLoading(){$('#crLoading').remove();}
    function renderRows(rows,total,page,tp){if(!rows||!rows.length){$('#crBody').html('<tr><td colspan="14" class="cr-empty"><i class="fa fa-inbox"></i>لا توجد قضايا مطابقة</td></tr>');return;}var h='',si=showAll?0:((page-1)*PER_PAGE);for(var i=0;i<rows.length;i++){var r=rows[i],c=r.persistence_color||'gray';h+='<tr class="cr-row cr-row-'+c+'">'+'<td data-label="#">'+(si+i+1)+'</td>'+'<td data-label="رقم القضية"><strong>'+esc(r.judiciary_number)+'</strong></td>'+'<td data-label="السنة">'+esc(r.case_year)+'</td>'+'<td data-label="المحكمة">'+esc(r.court_name)+'</td>'+'<td data-label="رقم العقد">'+esc(r.contract_id)+'</td>'+'<td data-label="العميل" title="'+esc(r.customer_name_full||r.customer_name)+'">'+esc(r.customer_name)+'</td>'+'<td data-label="الإجراء الأخير">'+esc(r.last_action_name)+'</td>'+'<td data-label="تاريخ آخر إجراء">'+esc(r.last_action_date)+'</td>'+'<td data-label="المثابرة"><span class="cr-badge cr-badge-'+c+'">'+(r.persistence_icon||'')+' '+esc(r.persistence_label)+'</span></td>'+'<td data-label="آخر متابعة">'+esc(r.last_followup_date)+'</td>'+'<td data-label="آخر تشييك وظيفة">'+esc(r.last_job_check_date)+'</td>'+'<td data-label="المحامي" title="'+esc(r.lawyer_name_full||r.lawyer_name)+'">'+esc(r.lawyer_name)+'</td>'+'<td data-label="الوظيفة">'+esc(r.job_title)+'</td>'+'<td data-label="نوع الوظيفة">'+esc(r.job_type)+'</td>'+'</tr>';}$('#crBody').html(h);}
    function updateStats(s){if(!s)return;$('#statTotal').text(parseInt(s.total)||0);$('#statRed').text(parseInt(s.cnt_red)||0);$('#statOrange').text(parseInt(s.cnt_orange)||0);$('#statGreen').text(parseInt(s.cnt_green)||0);$('.cr-filter-btn').eq(0).find('.filter-count').text(parseInt(s.total)||0);$('.cr-filter-btn').eq(1).find('.filter-count').text(parseInt(s.cnt_red)||0);$('.cr-filter-btn').eq(2).find('.filter-count').text(parseInt(s.cnt_orange)||0);$('.cr-filter-btn').eq(3).find('.filter-count').text(parseInt(s.cnt_green)||0);}
    function buildPager(total,tp,page){if(!total){$('#crPager').html('');return;}var h='';if(!showAll){h+='<button class="cr-pager-btn" data-action="prev"'+(page<=1?' disabled':'')+'><i class="fa fa-chevron-right"></i></button>';if(tp>1){var s=Math.max(1,page-3),e=Math.min(tp,page+3);if(s>1){h+='<button class="cr-pager-btn" data-page="1">1</button>';if(s>2)h+='<span class="cr-pager-info">...</span>';}for(var p=s;p<=e;p++)h+='<button class="cr-pager-btn'+(p===page?' active':'')+'" data-page="'+p+'">'+p+'</button>';if(e<tp){if(e<tp-1)h+='<span class="cr-pager-info">...</span>';h+='<button class="cr-pager-btn" data-page="'+tp+'">'+tp+'</button>';}}h+='<button class="cr-pager-btn" data-action="next"'+(page>=tp?' disabled':'')+'><i class="fa fa-chevron-left"></i></button>';h+='<span class="cr-pager-info">عرض '+((page-1)*PER_PAGE+1)+'-'+Math.min(page*PER_PAGE,total)+' من '+total+'</span>';}else{h+='<span class="cr-pager-info">عرض الكل: '+total+' سجل</span>';}h+='<button class="cr-pager-btn cr-pager-all'+(showAll?' active':'')+'" data-action="toggle-all">'+(showAll?'<i class="fa fa-list"></i> تصفح':'<i class="fa fa-th-list"></i> الكل')+'</button>';$('#crPager').html(h);}
    $(document).on('click','#crPager .cr-pager-btn',function(){var b=$(this);if(b.prop('disabled'))return;var a=b.data('action'),p=b.data('page');if(a==='prev')currentPage--;else if(a==='next')currentPage++;else if(a==='toggle-all'){showAll=!showAll;currentPage=1;}else if(p)currentPage=p;fetchData();});
    var timer;$('#crSearch').on('keyup',function(){clearTimeout(timer);var q=$(this).val().trim();timer=setTimeout(function(){searchQuery=q;currentPage=1;fetchData();},350);});
    $(document).on('click','.cr-filter-btn',function(){$('.cr-filter-btn').removeClass('active');$(this).addClass('active');activeFilter=$(this).data('filter');currentPage=1;showAll=false;fetchData();});
    $('#btnRefreshCache').on('click',function(){var b=$(this);b.addClass('loading').prop('disabled',true);$.getJSON(REFRESH_URL,function(){b.removeClass('loading').prop('disabled',false);fetchData();}).fail(function(){b.removeClass('loading').prop('disabled',false);});});
    $('#btnExport').on('click',function(){window.location.href=EXPORT_URL+'?filter='+encodeURIComponent(activeFilter)+'&search='+encodeURIComponent(searchQuery);});
    $('#btnPrint').on('click',function(){window.open(PRINT_URL+'?filter='+encodeURIComponent(activeFilter)+'&search='+encodeURIComponent(searchQuery),'_blank');});
    fetchData();
})(jQuery);
</script>
