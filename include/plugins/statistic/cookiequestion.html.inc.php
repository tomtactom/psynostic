<div style="all: unset; background-color: #E6E6E6; color: #151515; margin: 20px; padding: 25px; border-color: #F78181; border-width: 5px; border-style: dashed; border-radius: 10px; display: block; margin-top: 100px;">
          <form action="" method="post" style="overflow: hidden">
            <style type="text/css" scoped="">
                [type="checkbox"]:not(:checked),
                [type="checkbox"]:checked {
                    position: absolute;
                    left: -9999px;
                }
                [type="checkbox"]:not(:checked) + label,
                [type="checkbox"]:checked + label {
                    position: relative;
                    padding-left: 32px;
                    cursor: pointer;
                    margin-bottom: 4px;
                    display: inline-block;
                    font-size: 16px;
                }
                [type="checkbox"]:not(:checked) + label:before,
                [type="checkbox"]:checked + label:before {
                    content: '';
                    position: absolute;
                    left: 0px; top: 0px;
                    width: 22px; height: 22px;
                    border: 2px solid #cccccc;
                    background: #ffffff;
                    border-radius: 4px;
                    box-shadow: inset 0 1px 3px rgba(0,0,0,.1);
                }
                [type="checkbox"]:not(:checked) + label:after,
                [type="checkbox"]:checked + label:after {
                    content: '✔';
                    position: absolute;
                    top: 0px; left: 5px;
                    font-size: 20px;
                    line-height: 1.2;
                    color: #09ad7e;
                    transition: all .2s;
                }
                [type="checkbox"]:not(:checked) + label:after {
                    opacity: 0;
                    transform: scale(0);
                }
                [type="checkbox"]:checked + label:after {
                    opacity: 1;
                    transform: scale(1);
                }
                [type="checkbox"]:disabled:not(:checked) + label:before,
                [type="checkbox"]:disabled:checked + label:before {
                    box-shadow: none;
                    border-color: #999999;
                    background-color: #dddddd;
                }
                [type="checkbox"]:disabled:checked + label:after {
                    color: #999999;
                }
                [type="checkbox"]:disabled + label {
                    color: #aaaaaa;
                }
                [type="checkbox"]:checked:focus + label:before,
                [type="checkbox"]:not(:checked):focus + label:before {
                    border: 2px dotted #0000ff;
                }
                label:hover:before {
                    border: 2px solid #4778d9!important;
                    background: #ffffff
              </style>
              <input type="checkbox" name="choice" value="Yes" autocomplete="off" id="allowCookiesQuestionCheckbox">
              <label style="padding-left: 50px; font-weight: normal;" for="allowCookiesQuestionCheckbox">Ich bin mit der Speicherung von Cookies (Textdateien, welche über einen Internetbrowser auf einem Computersystem abgelegt und gespeichert werden) durch diese Internetseite einverstanden. Cookies werden zur Automatisierung der Vorgänge, zum Vereinfachen der Nutzung und zum Aufzeichnen von anonymen Nutzungsstatistiken verwendet. Mehr Informationen zu Cookies finden Sie auf der <a href="<?php echo $options['siteurl']; ?>/datenschutz" style="all: unset; text-decoration: underline;">Datenschutz Seite</a>.</label><br><br>
              <button type="submit" name="enter" style="all: unset; float: right; -webkit-border-radius: 5; -moz-border-radius: 5; border-radius: 5px; background: #d6d6d6; padding: 10px 20px 10px 20px; border: solid #b5b5b5 3px; text-decoration: none;">Fortfahren</button>
          </form>
        </div>