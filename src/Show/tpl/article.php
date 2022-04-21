<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Lato:wght@200;400;900&display=swap" rel="stylesheet">

<style>
    html.wf-active article {
        /*opacity: 1;*/
    }

    body {
        background: #fff;
        margin: 0;
        padding: 0;
    }

    article {
        opacity: 0;
        transition: opacity 1s 0.1s cubic-bezier(0.19, 1, 0.22, 1);
    }

    article.shown {
        opacity: 1;
    }
</style>

<script>
  console.log('%cРазработка – http://devnow.ru', 'color: #1789FC');
</script>

<div class="page--article">
    <article></article>

    <script type="text/javascript">

      window.onVMSAPIReady = function (api) {
        api.Article.enable({
          display_mode: 'desktop'
        });

        (function (d) {
          var config = {
              kitId: 'mok7yyy',
              scriptTimeout: 3000,
              async: true
            },
            h = d.documentElement, t = setTimeout(function () {
              h.className = h.className.replace(/\bwf-loading\b/g, "") + " wf-inactive";
            }, config.scriptTimeout), tk = d.createElement("script"), f = false, s = d.getElementsByTagName("script")[0],
            a;
          h.className += " wf-loading";
          tk.src = 'https://use.typekit.net/' + config.kitId + '.js';
          tk.async = true;
          tk.onload = tk.onreadystatechange = function () {
            a = this.readyState;
            if (f || a && a != "complete" && a != "loaded") return;
            f = true;
            clearTimeout(t);
            try {
              Typekit.load(config)
            } catch (e) {
            }
          };
          s.parentNode.insertBefore(tk, s)
        })(document);

        document.querySelectorAll('article')[0].classList.add('shown');
      };
    </script>

    <script>
      var htmls = {
        desktop: `<?php echo $desktop; ?>`,
        mobile: `<?php echo $mobile; ?>`,
      };
      var isMobile = false;
      var prev = null;

      function switchHtml(html) {
        var article = document.querySelector('article')

        if (window.VMS_API) {
          window.VMS_API.Article.disable()
        }

        article.innerHTML = html;

        if (window.VMS_API) {
          window.VMS_API.Article.enable({display_mode: 'desktop'})
        }
      }

      function onResize() {
        var w = window.innerWidth;

        isMobile = w < 768;

        if (prev !== isMobile) {
          prev = isMobile
          switchHtml(htmls[isMobile ? 'mobile' : 'desktop'])
        }
      }

      onResize()

      window.onresize = onResize;

    </script>

    <script src="//go.verstka.org/api.js" type="text/javascript"></script>

</div>
