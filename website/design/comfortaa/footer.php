    <footer>
        <img src="<?php echo $options['frontenddesignurl']; ?>/logo.png" alt="Logo Grau" href="">
        <section>
            <aside>
                <a href="https://www.instagram.com/" target="_blank"><img src="<?php echo $options['frontenddesignurl']; ?>/instagram-button.png"  alt="Instagram Button"></a>
                <a href="mailto:<?php echo $options['adminemail'] ?>" target="_blank"><img src="<?php echo $options['frontenddesignurl']; ?>/email-button.png"  alt="E-Mail Button"></a>
            </aside>
            <nav>
                <section>
                    <?php
						include('../website/menu_left.inc.php');
					?>
                </section>
                <section>
                    <ul>
                        <?php
							include('../website/menu_right.inc.php');
						?>
                    </ul>
                </section>
            </nav>
            <aside>
                <address>
                    <p>Powered by LupusGUI</p>
                </address>
                <div>
                    <p>&copy;<?php echo date('Y').' '.$options['sitename']; ?></p>
                </div>
            </aside>
        </section>
    </footer>
