    <!-- Footer -->
    <footer>
      <div class="container">
        <div class="footer-top">
          <div class="footer-brand">
            <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="nav-logo">
              <div class="nav-logo-mark">
                <img src="<?php echo get_template_directory_uri(); ?>/assets/smallpolesappicon.png"
                     alt="Small Poles Logo" style="width:64px;height:64px;border-radius:16px" />
              </div>
              <span class="nav-logo-text">Small Poles</span>
            </a>
            <p>The first fantasy football platform built exclusively for the Ghana Premier League.</p>
          </div>
          <div class="footer-links">
            <div class="footer-links-group">
              <h4>Platform</h4>
              <a href="<?php echo esc_url( home_url( '/#features' ) ); ?>">Features</a>
              <a href="<?php echo esc_url( home_url( '/#how-it-works' ) ); ?>">How It Works</a>
              <a href="<?php echo esc_url( home_url( '/#why-small-poles' ) ); ?>">Why Small Poles</a>
            </div>
            <div class="footer-links-group">
              <h4>News</h4>
              <a href="<?php echo esc_url( home_url( '/news/' ) ); ?>">News & Analysis</a>
              <a href="<?php echo esc_url( get_category_link( get_cat_ID( 'World Cup' ) ) ); ?>">World Cup 2026</a>
              <a href="<?php echo esc_url( get_category_link( get_cat_ID( 'GPL Analysis' ) ) ); ?>">GPL Analysis</a>
            </div>
            <div class="footer-links-group">
              <h4>Company</h4>
              <a href="<?php echo esc_url( home_url( '/about/' ) ); ?>">About</a>
              <a href="mailto:info@smallpoles.online">Partnerships</a>
              <a href="https://paystack.shop/pay/smallpoles" target="_blank" rel="noopener">Support Us</a>
              <a href="<?php echo esc_url( home_url( '/privacy/' ) ); ?>">Privacy Policy</a>
              <a href="<?php echo esc_url( home_url( '/terms/' ) ); ?>">Terms of Service</a>
            </div>
          </div>
        </div>
        <div class="footer-bottom">
          <span>&copy; <?php echo date('Y'); ?> Small Poles. All rights reserved.</span>
          <span>Built in Accra 🇬🇭</span>
        </div>
      </div>
    </footer>

<?php wp_footer(); ?>
</body>
</html>
