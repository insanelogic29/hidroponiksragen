<?php
  global $allowedposttags, $avata_animation, $avata_animation_delay;
  $section_title    = avata_option('section_title_1');
  $section_subtitle = avata_option('section_subtitle_1');
  $section_content  = wp_kses(avata_option('section_content_1'), $allowedposttags);
  $fullwidth         =  avata_option('section_fullwidth_1');
  $container         = 'container';
  if ($fullwidth=='1')
 	 $container         = 'container-fullwidth';

  ?>
<div class="section-content-wrap">
  <div class="<?php echo $container;?>">
  <?php if ( $section_title !='' || $section_subtitle !='' ){?>
    <div class="section-title-area">
      <h2 class="section-title avata-section_title_1 <?php echo $avata_animation;?>"  data-os-animation="fadeInUp" data-os-animation-delay="<?php echo $avata_animation_delay;?>"><?php echo esc_attr($section_title);?></h2>
      <h5 class="section-subtitle avata-section_subtitle_1 <?php echo $avata_animation;?>"  data-os-animation="fadeInUp" data-os-animation-delay="<?php echo $avata_animation_delay;?>"><?php echo wp_kses($section_subtitle, $allowedposttags);?></h5>
    </div>
    <?php }?>
    <div class="section-content avata-section_content_1 <?php echo $avata_animation;?>"  data-os-animation="fadeInUp" data-os-animation-delay="<?php echo $avata_animation_delay;?>">
     <?php echo do_shortcode($section_content);?>
     <div class="content-widgets <?php echo $avata_animation;?>"  data-os-animation="fadeInUp" data-os-animation-delay="<?php echo $avata_animation_delay;?>">
     <?php dynamic_sidebar("section-1"); ?>
     </div>
    </div>
  </div>
  </div>
