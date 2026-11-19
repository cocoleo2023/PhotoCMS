/* SEO-панель в редакторах: сниппет, счётчики, проверка ключевой фразы */
(function(){
  document.querySelectorAll('.seo-box').forEach(function(box){
    var f=box.closest('form'); if(!f) return;
    var q=function(n){return f.querySelector('[name="'+n+'"]')}, val=function(n){var x=q(n);return x?x.value.trim():''};
    var pv=box.querySelector('.seo-preview'), ck=box.querySelector('.seo-checks');
    function bar(len,min,max){var c=len===0?'e':(len<min||len>max?'w':'ok');return '<div class="seo-bar '+c+'"><i style="width:'+Math.min(100,len/max*100)+'%"></i></div>'}
    function render(){
      var title=val('meta_title'), t=title||val('title'), d=val('meta_description'), kw=val('focus_kw').toLowerCase(), slug=val('slug');
      var full=(title?t:t+box.dataset.tail); var path=box.dataset.prefix+slug;
      var shown=full.length>65?full.slice(0,62)+'…':full, dd=d.length>160?d.slice(0,157)+'…':d;
      pv.innerHTML='<div class="serp"><div class="u"></div><div class="t"></div><div class="d"></div></div>'
        +'<small class="muted">Заголовок: '+t.length+' зн. (оптимально 30–60)</small>'+bar(t.length,30,65)
        +'<small class="muted">Описание: '+d.length+' зн. (оптимально 70–160)</small>'+bar(d.length,70,170);
      pv.querySelector('.u').textContent=box.dataset.host+' › '+path.replace(/^\//,'').split('/').join(' › ');
      pv.querySelector('.t').textContent=shown; pv.querySelector('.d').textContent=dd||'Описание не задано — поисковик возьмёт фрагмент текста страницы.';
      var out=[];
      if(kw){
        var sl=kw.replace(/[^a-zа-яё0-9]+/g,'-');
        out.push([t.toLowerCase().indexOf(kw)>-1,'Ключевая фраза в заголовке']);
        out.push([d.toLowerCase().indexOf(kw)>-1,'Ключевая фраза в описании']);
        out.push([t.toLowerCase().indexOf(kw)===0||t.toLowerCase().indexOf(kw)<20,'Фраза ближе к началу заголовка']);
      } else out.push([false,'Задайте ключевую фразу, чтобы увидеть проверки']);
      if(q('og_image')) out.push([!!val('og_image'),'Есть изображение для соцсетей']);
      out.push([slug.length>0&&slug.length<=60,'Короткий адрес (до 60 символов)']);
      ck.innerHTML=''; out.forEach(function(x){var li=document.createElement('li');li.className=x[0]?'ok':'w';li.textContent=x[1];ck.appendChild(li)});
    }
    ['title','slug','meta_title','meta_description','focus_kw','og_image'].forEach(function(n){var x=q(n);if(x)x.addEventListener('input',render)});
    render();
  });
})();
