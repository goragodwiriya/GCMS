/**
 * GCMS Designer – Shared State
 *
 * Single mutable state object shared across all modules via the `ctx` pattern.
 * All modules import this and read/write it through `ctx.state`.
 *
 * @filesource js/designer/state.js
 */

export const state = {
  active: false,
  workspaceLoaded: false,
  workspaceStateAvailable: false,
  selectedBlock: null,
  selectedEditable: null,
  dragBlock: null,
  preview: false,
  panel: null,
  panelTitle: null,
  panelContent: null,
  /** นับรอบเปิดแผง — กัน onFormReady ค้างจาก initForm รอบก่อน */
  panelSessionId: 0,
  bar: null,
  dirty: false,
  saving: false,
  lastSavedAt: null,
  history: [],
  historyIndex: -1,
  /** snapshot ตอนเปิดโหมดแก้ไข — ใช้ยกเลิกกลับ original */
  originalSnapshot: null,
  historyTimer: null,
  draftTimer: null,
  discarding: false,
  restoring: false,
  copiedBlockStyle: null,
  cssVars: {},
  /** body layout classes — ค่าที่ถูกต้อง: 'wide' | 'fullwidth' | '' */
  bodyClasses: [],
  userTemplates: [],
  widgetManifests: [],
  widgetManifestsLoaded: false,
  cssVarFields: [
    {name: '--color-primary', label: 'Primary'},
    {name: '--color-primary-hover', label: 'Primary Hover'},
    {name: '--color-secondary', label: 'Secondary'},
    {name: '--color-background', label: 'Background'},
    {name: '--color-surface', label: 'Surface'},
    {name: '--color-text', label: 'Text'},
    {name: '--color-text-muted', label: 'Muted Text'}
  ],
  fonts: [
    {value: '', label: 'Default'},
    {value: '"Prompt", sans-serif', label: 'Prompt'},
    {value: '"Sarabun", sans-serif', label: 'Sarabun'},
    {value: '"Noto Sans Thai", sans-serif', label: 'Noto Sans Thai'},
    {value: '"IBM Plex Sans Thai", sans-serif', label: 'IBM Plex Sans Thai'},
    {value: '"Mitr", sans-serif', label: 'Mitr'},
    {value: '"Kanit", sans-serif', label: 'Kanit'},
    {value: '"Charm", sans-serif', label: 'Charm'},
    {value: '"Itim", sans-serif', label: 'Itim'},
    {value: '"Kodchasan", sans-serif', label: 'Kodchasan'}
  ],
  templates: [
    {
      id: 'hero-banner',
      category: 'media',
      title: 'Hero Banner',
      description: 'Full-screen hero with background image, headline, subtitle and CTA',
      html: `
        <section class="hero-banner gcms-custom-block" data-block-type="hero-banner" data-editor-template="hero-banner" data-gcms-custom="1">
          <div class="container">
            <div class="section-content" data-repeat-container data-drop-zone>
              <h1 data-editable="text" data-editor-field-name="hero_banner_title">ยินดีต้อนรับสู่โรงเรียนวิทยาศาสตร์แห่งอนาคต</h1>
              <p data-editable="text" data-editor-field-name="hero_banner_subtitle" data-repeat-item>ศูนย์การเรียนรู้ที่มุ่งเน้นการพัฒนาทักษะด้านวิทยาศาสตร์ เทคโนโลยี วิศวกรรม และคณิตศาสตร์ (STEM)
                สำหรับเยาวชนไทย เพื่อเตรียมความพร้อมสู่โลกอนาคต</p>
              <div>
                <div class="section-actions" data-repeat-container data-drop-zone>
                  <a data-editable="text" data-editor-field-name="hero_banner_button" href="#" class="btn btn-orange large" data-repeat-item>สมัครเรียน</a>
                  <button data-editable="text" data-editor-field-name="hero_banner_next" class="btn" data-repeat-item>ลงทะเบียน</button>
                </div>
              </div>
            </div>
          </div>
        </section>`
    },
    {
      id: 'hero',
      category: 'media',
      title: 'Hero Section',
      description: 'Hero with image column — heading, description and CTA button',
      html: `
        <section class="hero gcms-custom-block" data-block-type="hero" data-editor-template="with-image" data-gcms-custom="1">
          <div class="container">
            <div class="section-row">
              <div class="section-content" data-drop-zone>
                <h1 data-editable="text" data-editor-field-name="hero_title">ยินดีต้อนรับสู่โรงเรียนวิทยาศาสตร์แห่งอนาคต</h1>
                <p data-editable="text" data-editor-field-name="hero_description">ศูนย์การเรียนรู้ที่มุ่งเน้นการพัฒนาทักษะด้านวิทยาศาสตร์ เทคโนโลยี วิศวกรรม และคณิตศาสตร์ (STEM)
                  สำหรับเยาวชนไทย เพื่อเตรียมความพร้อมสู่โลกอนาคต</p>
                <div>
                  <div class="section-actions" data-repeat-container data-drop-zone>
                    <a href="#" class="btn btn-orange large" data-editable="text" data-editor-field-name="hero_button" data-repeat-item>สมัครเรียน</a>
                  </div>
                </div>
              </div>
              <div class="section-image">
                <img src="https://picsum.photos/800/600" alt="นักเรียนในห้องปฏิบัติการ" data-editable="image" data-editor-field-name="hero_image" loading="lazy">
              </div>
            </div>
          </div>
        </section>`
    },
    {
      id: 'features',
      category: 'layout',
      title: 'Features',
      description: 'Icon cards in a 3-column grid',
      html: `
        <section class="features gcms-custom-block" data-block-type="features" data-editor-template="grid-3" data-gcms-custom="1">
          <div class="container">
            <div class="section-title">
              <h2 data-editable="text" data-editor-field-name="feature_list_title">จุดเด่นของโรงเรียน</h2>
              <p data-editable="text" data-editor-field-name="feature_list_subtitle">สิ่งที่ทำให้โรงเรียนวิทยาศาสตร์แห่งอนาคตแตกต่าง</p>
            </div>
            <div class="contents-grid">
              <div class="section-card section-icon">
                <i class="icon-dev" data-editable="icon" data-editor-field-name="feature_1_icon"></i>
                <div class="section-content" data-drop-zone>
                  <h3 data-editable="text" data-editor-field-name="feature_1_title">ห้องปฏิบัติการทันสมัย</h3>
                  <p data-editable="text" data-editor-field-name="feature_1_text">ห้องปฏิบัติการวิทยาศาสตร์ที่ทันสมัยพร้อมเครื่องมือและอุปกรณ์ครบครัน
                    เพื่อให้นักเรียนได้ลงมือทำการทดลองจริง</p>
                </div>
              </div>
              <div class="section-card section-icon">
                <i class="icon-elearning" data-editable="icon" data-editor-field-name="feature_2_icon"></i>
                <div class="section-content" data-drop-zone>
                  <h3 data-editable="text" data-editor-field-name="feature_2_title">ครูผู้เชี่ยวชาญ</h3>
                  <p data-editable="text" data-editor-field-name="feature_2_text">ครูผู้สอนที่มีประสบการณ์และความเชี่ยวชาญสูง
                    จบการศึกษาระดับปริญญาโทและเอกจากสถาบันชั้นนำทั้งในและต่างประเทศ</p>
                </div>
              </div>
              <div class="section-card section-icon">
                <i class="icon-rocket" data-editable="icon" data-editor-field-name="feature_3_icon"></i>
                <div class="section-content" data-drop-zone>
                  <h3 data-editable="text" data-editor-field-name="feature_3_title">หลักสูตรที่ทันสมัย</h3>
                  <p data-editable="text" data-editor-field-name="feature_3_text">หลักสูตรการเรียนการสอนที่ทันสมัย เน้นการเรียนรู้แบบ Active Learning
                    โดยให้นักเรียนได้ลงมือปฏิบัติจริงและคิดวิเคราะห์</p>
                </div>
              </div>
            </div>
          </div>
        </section>`
    },
    {
      id: 'about',
      category: 'content',
      title: 'About',
      description: 'About section with text and image side by side',
      html: `
        <section class="about gcms-custom-block" data-block-type="about" data-editor-template="standard" data-gcms-custom="1">
          <div class="container">
            <div class="section-row">
              <div class="about-content flex column left gap-6" data-drop-zone>
                <h2 data-editable="text" data-editor-field-name="about_title">เกี่ยวกับโรงเรียน</h2>
                <p data-editable="text" data-editor-field-name="about_text_1">โรงเรียนวิทยาศาสตร์แห่งอนาคตก่อตั้งขึ้นในปี พ.ศ. 2560
                  ด้วยปณิธานที่จะพัฒนาเยาวชนไทยให้มีความรู้ความสามารถด้านวิทยาศาสตร์และเทคโนโลยี เพื่อเป็นกำลังสำคัญในการพัฒนาประเทศในอนาคต</p>
                <p data-editable="text" data-editor-field-name="about_text_2">ในปัจจุบัน โรงเรียนให้การศึกษาแก่นักเรียนตั้งแต่ระดับมัธยมศึกษาปีที่ 1 จนถึงมัธยมศึกษาปีที่ 6
                  โดยมีนักเรียนทั้งหมดกว่า 1,200 คน และมีบุคลากรครูผู้สอนกว่า 80 คน</p>
                <div>
                  <div class="section-actions" data-repeat-container data-drop-zone>
                    <a href="#" class="btn btn-green small" data-editable="text" data-editor-field-name="about_button" data-repeat-item>อ่านต่อ</a>
                  </div>
                </div>
              </div>
              <div class="about-image">
                <img src="https://picsum.photos/800/600?random=2" alt="อาคารเรียนโรงเรียน" data-editable="image" data-editor-field-name="about_image" loading="lazy">
              </div>
            </div>
          </div>
        </section>`
    },
    {
      id: 'news',
      category: 'media',
      title: 'Card grid',
      description: 'News or article cards with image, date and link',
      html: `
        <section class="news gcms-custom-block" data-block-type="features" data-editor-template="grid-3" data-gcms-custom="1">
          <div class="container">
            <div class="section-title">
              <h2 data-editable="text" data-editor-field-name="news_title">ข่าวสารและกิจกรรม</h2>
              <p data-editable="text" data-editor-field-name="news_subtitle">ติดตามข่าวสารและกิจกรรมล่าสุดของโรงเรียน</p>
            </div>
            <div class="contents-grid">
              <div class="content-card news-card">
                <div class="card-image">
                  <img src="https://picsum.photos/800/500?random=8" alt="งานวันวิทยาศาสตร์" data-editable="image" data-editor-field-name="news_1_image" loading="lazy">
                </div>
                <div class="card-body" data-drop-zone>
                  <span class="news-date" data-editable="text" data-editor-field-name="news_1_date">10 มกราคม 2025</span>
                  <h3 data-editable="text" data-editor-field-name="news_1_title">งานสัปดาห์วิทยาศาสตร์ประจำปี 2025</h3>
                  <p data-editable="text" data-editor-field-name="news_1_text">โดยมีการจัดแสดงโครงงานวิทยาศาสตร์ของนักเรียน</p>
                  <a href="#" class="news-link" data-editable="text" data-editor-field-name="news_1_link">อ่านเพิ่มเติม</a>
                </div>
              </div>
              <div class="content-card news-card">
                <div class="card-image">
                  <img src="https://picsum.photos/800/500?random=9" alt="แข่งขันโอลิมปิก" data-editable="image" data-editor-field-name="news_2_image" loading="lazy">
                </div>
                <div class="card-body" data-drop-zone>
                  <span class="news-date" data-editable="text" data-editor-field-name="news_2_date">5 กุมภาพันธ์ 2025</span>
                  <h3 data-editable="text" data-editor-field-name="news_2_title">นักเรียนคว้าเหรียญทองโอลิมปิกวิชาการ</h3>
                  <p data-editable="text" data-editor-field-name="news_2_text">นักเรียนจากโรงเรียนวิทยาศาสตร์แห่งอนาคตคว้าเหรียญทองจากการแข่งขันโอลิมปิกวิชาการระดับนานาชาติในสาขาฟิสิกส์และคณิตศาสตร์</p>
                  <a href="#" class="news-link" data-editable="text" data-editor-field-name="news_2_link">อ่านเพิ่มเติม</a>
                </div>
              </div>
              <div class="content-card news-card">
                <div class="card-image">
                  <img src="https://picsum.photos/800/500?random=10" alt="เปิดรับสมัคร" data-editable="image" data-editor-field-name="news_3_image" loading="lazy">
                </div>
                <div class="card-body" data-drop-zone>
                  <span class="news-date" data-editable="text" data-editor-field-name="news_3_date">15 มีนาคม 2025</span>
                  <h3 data-editable="text" data-editor-field-name="news_3_title">เปิดรับสมัครนักเรียนใหม่ ปีการศึกษา 2025</h3>
                  <p data-editable="text" data-editor-field-name="news_3_text">โรงเรียนวิทยาศาสตร์แห่งอนาคตเปิดรับสมัครนักเรียนใหม่ระดับชั้นมัธยมศึกษาปีที่ 1 และมัธยมศึกษาปีที่ 4
                    ประจำปีการศึกษา 2025</p>
                  <a href="#" class="news-link" data-editable="text" data-editor-field-name="news_3_link">อ่านเพิ่มเติม</a>
                </div>
              </div>
            </div>
          </div>
        </section>`
    },
    {
      id: 'team',
      category: 'media',
      title: 'Team / people',
      description: 'Introduce leadership team — adapt for any people section.',
      html: `
        <section class="team gcms-custom-block" data-block-type="about" data-editor-template="team" data-gcms-custom="1">
          <div class="container">
            <div class="section-title">
              <h2 data-editable="text" data-editor-field-name="team_title">ทีมผู้บริหารและคณาจารย์</h2>
              <p data-editable="text" data-editor-field-name="team_subtitle">ผู้ทรงคุณวุฒิมากประสบการณ์ที่พร้อมถ่ายทอดความรู้ให้แก่นักเรียน</p>
            </div>
            <div class="contents-grid">
              <div class="content-card">
                <div class="member-photo">
                  <img src="https://picsum.photos/400/400?random=5" alt="ผู้อำนวยการโรงเรียน" data-editable="image" data-editor-field-name="team_member_1_photo">
                </div>
                <h3 data-editable="text" data-editor-field-name="team_member_1_name">ดร.สมชาย วิทยาวุฒิ</h3>
                <p class="member-role" data-editable="text" data-editor-field-name="team_member_1_role">ผู้อำนวยการโรงเรียน</p>
                <p class="member-bio" data-editable="text" data-editor-field-name="team_member_1_bio">จบการศึกษาระดับปริญญาเอกด้านการบริหารการศึกษา มีประสบการณ์ด้านการบริหารโรงเรียนมากกว่า 20 ปี</p>
                <div class="member-social">
                  <a href="#"><i class="icon-linkedin" data-editable="icon" data-editor-field-name="team_member_1_linkedin"></i></a>
                  <a href="#"><i class="icon-twitter"  data-editable="icon" data-editor-field-name="team_member_1_twitter"></i></a>
                  <a href="#"><i class="icon-email" data-editable="icon" data-editor-field-name="team_member_1_email"></i></a>
                </div>
              </div>
              <div class="content-card">
                <div class="member-photo">
                  <img src="https://picsum.photos/400/400?random=6" alt="รองผู้อำนวยการ" data-editable="image" data-editor-field-name="team_member_2_photo">
                </div>
                <h3 data-editable="text" data-editor-field-name="team_member_2_name">รศ.ดร.สมหญิง นวัตกรรม</h3>
                <p class="member-role" data-editable="text" data-editor-field-name="team_member_2_role">รองผู้อำนวยการฝ่ายวิชาการ</p>
                <p class="member-bio" data-editable="text" data-editor-field-name="team_member_2_bio">ผู้เชี่ยวชาญด้านการสอนวิทยาศาสตร์ จบการศึกษาจากมหาวิทยาลัยชั้นนำในสหรัฐอเมริกา</p>
                <div class="member-social">
                  <a href="#"><i class="icon-linkedin" data-editable="icon" data-editor-field-name="team_member_2_linkedin"></i></a>
                  <a href="#"><i class="icon-twitter" data-editable="icon" data-editor-field-name="team_member_2_twitter"></i></a>
                  <a href="#"><i class="icon-email" data-editable="icon" data-editor-field-name="team_member_2_email"></i></a>
                </div>
              </div>
              <div class="content-card">
                <div class="member-photo">
                  <img src="https://picsum.photos/400/400?random=7" alt="หัวหน้ากลุ่มสาระ" data-editable="image" data-editor-field-name="team_member_3_photo">
                </div>
                <h3 data-editable="text" data-editor-field-name="team_member_3_name">อาจารย์พิริยะ ดาวประดิษฐ์</h3>
                <p class="member-role" data-editable="text" data-editor-field-name="team_member_3_role">หัวหน้ากลุ่มสาระวิทยาศาสตร์</p>
                <p class="member-bio" data-editable="text" data-editor-field-name="team_member_3_bio">ผู้เชี่ยวชาญด้านฟิสิกส์ เคยได้รับรางวัลครูดีเด่นระดับประเทศ และมีผลงานวิจัยตีพิมพ์ระดับนานาชาติ</p>
                <div class="member-social">
                  <a href="#"><i class="icon-linkedin" data-editable="icon" data-editor-field-name="team_member_3_linkedin"></i></a>
                  <a href="#"><i class="icon-twitter" data-editable="icon" data-editor-field-name="team_member_3_twitter"></i></a>
                  <a href="#"><i class="icon-email" data-editable="icon" data-editor-field-name="team_member_3_email"></i></a>
                </div>
              </div>
            </div>
          </div>
        </section>`
    },
    {
      id: 'testimonials',
      category: 'media',
      title: 'Testimonials / feedback',
      description: 'Feedback from students and parents — adapt for any testimonial content.',
      html: `
        <section class="testimonials gcms-custom-block" data-block-type="testimonials" data-editor-template="grid" data-gcms-custom="1">
          <div class="container">
            <div class="section-title">
              <h2 data-editable="text" data-editor-field-name="testimonials_title">เสียงจากนักเรียนและผู้ปกครอง</h2>
              <p data-editable="text" data-editor-field-name="testimonials_subtitle">ความคิดเห็นจากนักเรียนและผู้ปกครองที่มีต่อโรงเรียนของเรา</p>
            </div>
            <div class="contents-grid">
              <div class="content-card">
                <div class="testimonial-rating">
                  <i class="icon-star2"></i>
                  <i class="icon-star2"></i>
                  <i class="icon-star2"></i>
                  <i class="icon-star2"></i>
                  <i class="icon-star2"></i>
                </div>
                <p class="testimonial-text" data-editable="text" data-editor-field-name="testimonial_1_text">สภาพแวดล้อมดี ครูเอาใจใส่นักเรียนมากครับ</p>
                <div class="testimonial-author">
                  <img src="https://picsum.photos/100/100?random=11" alt="ผู้ปกครอง" class="testimonial-avatar" data-editable="image" data-editor-field-name="testimonial_1_image" loading="lazy">
                  <div class="author-info">
                    <h4 data-editable="text" data-editor-field-name="testimonial_1_author">คุณพ่อสมศักดิ์</h4>
                    <p data-editable="text" data-editor-field-name="testimonial_1_role">ผู้ปกครองนักเรียนชั้น ม.3</p>
                  </div>
                </div>
              </div>
              <div class="content-card">
                <div class="testimonial-rating">
                  <i class="icon-star2"></i>
                  <i class="icon-star2"></i>
                  <i class="icon-star2"></i>
                  <i class="icon-star2"></i>
                  <i class="icon-star2"></i>
                </div>
                <p class="testimonial-text" data-editable="text" data-editor-field-name="testimonial_2_text">ห้องแล็บและกิจกรรมเสริมทำให้ผมชอบวิทยาโดยตรง</p>
                <div class="testimonial-author">
                  <img src="https://picsum.photos/100/100?random=12" alt="นักเรียน" class="testimonial-avatar" data-editable="image" data-editor-field-name="testimonial_2_image" loading="lazy">
                  <div class="author-info">
                    <h4 data-editable="text" data-editor-field-name="testimonial_2_author">นายจิรายุ</h4>
                    <p data-editable="text" data-editor-field-name="testimonial_2_role">นักเรียนชั้น ม.6</p>
                  </div>
                </div>
              </div>
              <div class="content-card">
                <div class="testimonial-rating">
                  <i class="icon-star2"></i>
                  <i class="icon-star2"></i>
                  <i class="icon-star2"></i>
                  <i class="icon-star2"></i>
                  <i class="icon-star1"></i>
                </div>
                <p class="testimonial-text" data-editable="text" data-editor-field-name="testimonial_3_text">เนื้อหาแน่นแต่บางครั้งงานกลุ่มเยอะไปนิด</p>
                <div class="testimonial-author">
                  <img src="https://picsum.photos/100/100?random=13" alt="นักเรียน" class="testimonial-avatar" data-editable="image" data-editor-field-name="testimonial_3_image" loading="lazy">
                  <div class="author-info">
                    <h4 data-editable="text" data-editor-field-name="testimonial_3_author">นางสาวกนกวรรณ</h4>
                    <p data-editable="text" data-editor-field-name="testimonial_3_role">นักเรียนชั้น ม.5</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>`,
    },
    {
      id: 'contact-form',
      category: 'content',
      title: 'Contact form',
      description: 'Contact section with text and inquiry form',
      html: `
        <section class="cta gcms-custom-block" data-block-type="cta" data-editor-template="with-form" data-gcms-custom="1">
          <div class="container">
            <div class="section-row">
              <div class="section-content" data-drop-zone>
                <h2 data-editable="text" data-editor-field-name="cta_title">สนใจสมัครเรียนหรือติดต่อเรา</h2>
                <p data-editable="text" data-editor-field-name="cta_text">กรอกแบบฟอร์มเพื่อรับข้อมูลเพิ่มเติมเกี่ยวกับหลักสูตรการเรียนการสอน การเยี่ยมชมโรงเรียน หรือการสมัครเรียน</p>
                <ul class="cta-features">
                  <li data-editable="text" data-editor-field-name="cta_feature_1">รับข้อมูลเกี่ยวกับหลักสูตรการเรียนการสอน</li>
                  <li data-editable="text" data-editor-field-name="cta_feature_2">นัดหมายเพื่อเยี่ยมชมโรงเรียน</li>
                  <li data-editable="text" data-editor-field-name="cta_feature_3">สอบถามเกี่ยวกับการสมัครเรียน</li>
                </ul>
              </div>
              <div class="feature-form">
                <form>
                  <fieldset>
                    <div>
                      <span class="form-control icon-user">
                        <input type="text" placeholder="ชื่อ-นามสกุล" data-editable="text" data-editor-field-name="cta_form_name">
                      </span>
                    </div>
                    <div>
                      <span class="form-control icon-email">
                        <input type="email" placeholder="อีเมล" data-editable="text" data-editor-field-name="cta_form_email">
                      </span>
                    </div>
                    <div>
                      <span class="form-control icon-phone">
                        <input type="tel" placeholder="เบอร์โทรศัพท์" data-editable="text" data-editor-field-name="cta_form_phone">
                      </span>
                    </div>
                    <div>
                      <span class="form-control icon-list">
                        <select data-editable="text" data-editor-field-name="cta_form_topic">
                          <option>เลือกหัวข้อที่สนใจ</option>
                          <option>ข้อมูลหลักสูตรการเรียน</option>
                          <option>การเยี่ยมชมโรงเรียน</option>
                          <option>การสมัครเรียน</option>
                          <option>อื่นๆ</option>
                        </select>
                      </span>
                    </div>
                  </fieldset>
                  <fieldset class="submit">
                    <button type="submit" class="btn btn-orange icon-send" data-editable="text" data-editor-field-name="cta_form_button">ส่งข้อความ</button>
                  </fieldset>
                </form>
              </div>
            </div>
          </div>
        </section>`
    },
    {
      id: 'home-cms',
      category: 'content',
      title: 'CMS content',
      description: 'Main CMS content area ({DETAIL})',
      html: `
        <section class="home gcms-custom-block" data-block-type="home-cms" data-editor-template="content" data-gcms-custom="1">
          <div class="container">
            <div class="home-content">
              {DETAIL}
            </div>
          </div>
        </section>`
    },
    {
      id: 'image-text',
      category: 'media',
      title: 'Image + text',
      description: 'Image + text rows, repeatable — add/remove rows with reverse layout support',
      html: `
        <section class="section-bg gcms-custom-block" data-block-type="image-text" data-editor-template="image-text" data-gcms-custom="1">
          <div class="container">
            <div class="section-title">
              <h2 data-editable="text" data-editor-field-name="it_header_title">หลักสูตรการเรียน</h2>
              <p data-editable="text" data-editor-field-name="it_header_subtitle">หลักสูตรที่เน้นความเป็นเลิศทางวิชาการและพัฒนาทักษะแห่งอนาคต</p>
            </div>
            <div class="sections-list" data-repeat-container>
              <div class="section-row" data-repeat-item>
                <div class="section-content" data-drop-zone>
                  <h3 data-editable="text" data-editor-field-name="it_title">เกี่ยวกับโรงเรียน</h3>
                  <p data-editable="text" data-editor-field-name="it_subtitle">โรงเรียนวิทยาศาสตร์แห่งอนาคตก่อตั้งขึ้นในปี พ.ศ. 2560 ด้วยปณิธานที่จะพัฒนาเยาวชนไทยให้มีความรู้ความสามารถด้านวิทยาศาสตร์และเทคโนโลยี เพื่อเป็นกำลังสำคัญในการพัฒนาประเทศในอนาคต</p>
                  <div>
                    <div class="section-actions" data-repeat-container data-drop-zone>
                      <a data-editable="text" data-editor-field-name="it_button" href="#" class="btn btn-orange large" data-repeat-item>อ่านเพิ่มเติม</a>
                    </div>
                  </div>
                </div>
                <div class="section-image">
                  <img src="https://picsum.photos/800/600" alt="" data-editable="image" data-editor-field-name="it_image">
                </div>
              </div>
            </div>
          </div>
        </section>`
    },
    {
      id: 'announcement',
      category: 'content',
      title: 'Announcement / CTA',
      description: 'Text block with a button',
      html: `
        <section class="section-bg gcms-custom-block" data-block-type="announcement" data-editor-template="announcement" data-gcms-custom="1">
          <div class="container">
            <div class="section-title">
              <span data-editable="text" data-editor-field-name="custom_eyebrow" class="section-eyebrow" aria-label="หมวดหมู่">เริ่มต้นฟรี · ไม่ต้องผูกบัตร</span>
              <h2 data-editable="text" data-editor-field-name="custom_title">สร้างเว็บไซต์ที่ rank ได้ ใน 10 นาที</h2>
              <p data-editable="text" data-editor-field-name="custom_text">เครื่องมือ SEO ครบชุดสำหรับธุรกิจขนาดเล็ก วิเคราะห์ keyword, ตรวจ on-page, และ track ranking อัตโนมัติ</p>
            </div>
            <div>
              <div class="section-actions" data-repeat-container data-drop-zone>
                <a class="btn btn-primary" href="#" data-editable="text" data-editor-field-name="custom_button" data-repeat-item>ดูรายละเอียด</a>
              </div>
            </div>
          </div>
        </section>`
    },
    {
      id: 'info',
      category: 'content',
      title: 'Info snippet',
      description: 'Heading and short text',
      html: `
        <section class="section-bg gcms-custom-block" data-block-type="info" data-editor-template="info" data-gcms-custom="1">
          <div class="section-title">
            <h2 data-editable="text" data-editor-field-name="custom_info_title">หัวข้อข้อมูล</h2>
          </div>
          <div class="section-body" data-drop-zone>
            <p data-editable="text" data-editor-field-name="custom_info_text">ใส่รายละเอียดที่ต้องการแสดงบนหน้าแรก</p>
          </div>
        </section>`
    },
    {
      id: 'quote',
      category: 'content',
      title: 'Quote / vision',
      description: 'Highlighted quote text',
      html: `
        <section class="section-bg gcms-custom-block" data-block-type="quote" data-editor-template="quote" data-gcms-custom="1">
          <div class="section-body gcms-quote" data-drop-zone>
            <blockquote data-editable="text" data-editor-field-name="quote_text">"มุ่งพัฒนาผู้เรียนให้มีคุณธรรม ความรู้ และทักษะสู่อนาคต"</blockquote>
            <p data-editable="text" data-editor-field-name="quote_author">ผู้บริหารโรงเรียน</p>
          </div>
        </section>`
    },
    {
      id: 'stats-bar',
      category: 'content',
      title: 'Stats bar',
      description: 'Large number + label statistics, repeatable',
      html: `
        <section class="section-bg gcms-custom-block" data-block-type="stats-bar" data-editor-template="stats-bar" data-gcms-custom="1">
          <div class="container">
            <div class="section-title">
              <h2 data-editable="text" data-editor-field-name="stats_title">ตัวเลขที่น่าภูมิใจ</h2>
              <p data-editable="text" data-editor-field-name="stats_text">ตัวเลขที่น่าภูมิใจของโรงเรียน</p>
            </div>
            <div class="gcms-stats-bar" data-repeat-container data-drop-zone>
              <div class="gcms-stat-item" data-repeat-item>
                <div class="gcms-stat-value">
                  <strong data-component="counter" data-editor-field-name="stat_1_value" data-end="1234" data-duration="2000" data-format="number" data-separator="," data-scroll-trigger="true"></strong>
                </div>
                <span class="gcms-stat-label" data-editable="text" data-editor-field-name="stat_1_label">นักเรียน</span>
              </div>
              <div class="gcms-stat-item" data-repeat-item>
                <div class="gcms-stat-value">
                  <strong data-component="counter" data-editor-field-name="stat_2_value" data-end="56" data-duration="2000" data-format="number" data-scroll-trigger="true"></strong>
                </div>
                <span class="gcms-stat-label" data-editable="text" data-editor-field-name="stat_2_label">ครูและบุคลากร</span>
              </div>
              <div class="gcms-stat-item" data-repeat-item>
                <div class="gcms-stat-value">
                  <strong data-component="counter" data-editor-field-name="stat_3_value" data-end="30" data-suffix="+" data-duration="2000" data-format="number" data-scroll-trigger="true"></strong>
                </div>
                <span class="gcms-stat-label" data-editable="text" data-editor-field-name="stat_3_label">ปีแห่งประสบการณ์</span>
              </div>
            </div>
          </div>
        </section>`
    },
    {
      id: 'footer-columns',
      category: 'content',
      title: 'Footer — 3 columns',
      description: 'Dark footer with contact, links and info columns',
      html: `
        <footer class="footer gcms-custom-block" data-block-type="footer" data-editor-template="footer-columns" data-gcms-custom="1">
          <div class="container">
            <div class="footer-content">
              <div class="footer-section" data-drop-zone>
                <h3 class="footer-title" data-editable="text" data-editor-field-name="footer_col1_title">ติดต่อเรา</h3>
                <ul class="footer-links" data-repeat-container>
                  <li data-repeat-item><span class="icon-office" data-editable="text" data-editor-field-name="footer_col1_item_1">โรงเรียนวิทยาศาสตร์แห่งอนาคต</span></li>
                  <li data-repeat-item><span class="icon-phone" data-editable="text" data-editor-field-name="footer_col1_item_2">02-123-4567</span></li>
                  <li data-repeat-item><span class="icon-email" data-editable="text" data-editor-field-name="footer_col1_item_3">contact@school.ac.th</span></li>
                </ul>
              </div>
              <div class="footer-section" data-drop-zone>
                <h3 class="footer-title" data-editable="text" data-editor-field-name="footer_col2_title">ลิงก์ด่วน</h3>
                <ul class="footer-links" data-repeat-container>
                  <li data-repeat-item><a href="#" data-editable="text" data-editor-field-name="footer_col2_link_1">เกี่ยวกับเรา</a></li>
                  <li data-repeat-item><a href="#" data-editable="text" data-editor-field-name="footer_col2_link_2">หลักสูตร</a></li>
                  <li data-repeat-item><a href="#" data-editable="text" data-editor-field-name="footer_col2_link_3">ข่าวสาร</a></li>
                  <li data-repeat-item><a href="#" data-editable="text" data-editor-field-name="footer_col2_link_4">ติดต่อเรา</a></li>
                </ul>
              </div>
              <div class="footer-section" data-drop-zone>
                <h3 class="footer-title" data-editable="text" data-editor-field-name="footer_col3_title">เวลาทำการ</h3>
                <ul class="footer-links" data-repeat-container>
                  <li data-repeat-item><span data-editable="text" data-editor-field-name="footer_col3_item_1">จันทร์ – ศุกร์: 08:00 – 16:00</span></li>
                  <li data-repeat-item><span data-editable="text" data-editor-field-name="footer_col3_item_2">เสาร์: 08:00 – 12:00</span></li>
                  <li data-repeat-item><span data-editable="text" data-editor-field-name="footer_col3_item_3">วันอาทิตย์และวันหยุดนักขัตฤกษ์: ปิด</span></li>
                </ul>
              </div>
            </div>
            <div class="footer-bottom">
              <p data-editable="text" data-editor-field-name="footer_copyright">Copyright © 2026 โรงเรียนวิทยาศาสตร์แห่งอนาคต</p>
            </div>
          </div>
        </footer>`
    },
    {
      id: 'footer-light',
      category: 'content',
      title: 'Footer — light 2 columns',
      description: 'Light footer with brand intro and quick links',
      html: `
        <footer class="footer gcms-footer-light gcms-custom-block" data-block-type="footer" data-editor-template="footer-light" data-gcms-custom="1">
          <div class="container">
            <div class="footer-content gcms-footer-split">
              <div class="footer-section footer-brand" data-drop-zone>
                <h3 class="footer-title" data-editable="text" data-editor-field-name="footer_brand_title">โรงเรียนวิทยาศาสตร์แห่งอนาคต</h3>
                <p data-editable="text" data-editor-field-name="footer_brand_text">ศูนย์การเรียนรู้ที่มุ่งเน้น STEM เพื่อเตรียมเยาวชนไทยสู่โลกอนาคต</p>
              </div>
              <div class="footer-section" data-drop-zone>
                <h3 class="footer-title" data-editable="text" data-editor-field-name="footer_links_title">ลิงก์ด่วน</h3>
                <ul class="footer-links gcms-footer-links-inline" data-repeat-container>
                  <li data-repeat-item><a href="#" data-editable="text" data-editor-field-name="footer_link_1">หน้าแรก</a></li>
                  <li data-repeat-item><a href="#" data-editable="text" data-editor-field-name="footer_link_2">หลักสูตร</a></li>
                  <li data-repeat-item><a href="#" data-editable="text" data-editor-field-name="footer_link_3">ข่าวสาร</a></li>
                  <li data-repeat-item><a href="#" data-editable="text" data-editor-field-name="footer_link_4">ติดต่อเรา</a></li>
                </ul>
              </div>
            </div>
            <div class="footer-bottom">
              <p data-editable="text" data-editor-field-name="footer_copy">Copyright © 2026 โรงเรียนวิทยาศาสตร์แห่งอนาคต · สงวนลิขสิทธิ์</p>
            </div>
          </div>
        </footer>`
    },
    /* ── Column layout containers (drop zone targets for widgets/elements) ── */
    {
      id: 'columns-1',
      category: 'layout',
      title: '1 Column',
      description: 'Blank 1-column section',
      html: `
        <section class="section-bg gcms-custom-block" data-block-type="columns" data-editor-template="columns-1" data-gcms-custom="1" data-editor-label="1 Column">
          <div class="container" data-editable="text" data-drop-zone>
            <p data-editable="text">Content here</p>
          </div>
        </section>`
    },
    {
      id: 'columns-2',
      category: 'layout',
      title: '2 Columns',
      description: 'Two equal columns — drag widgets or elements inside',
      html: `
        <section class="section-bg gcms-custom-block" data-block-type="columns" data-editor-template="columns-2" data-gcms-custom="1" data-editor-label="2 Columns">
          <div class="container">
            <div class="gcms-columns gcms-columns-2">
              <div class="gcms-column" data-gcms-column="1" data-drop-zone></div>
              <div class="gcms-column" data-gcms-column="2" data-drop-zone></div>
            </div>
          </div>
        </section>`
    },
    {
      id: 'columns-3',
      category: 'layout',
      title: '3 Columns',
      description: 'Three equal columns — drag widgets or elements inside',
      html: `
        <section class="section-bg gcms-custom-block" data-block-type="columns" data-editor-template="columns-3" data-gcms-custom="1" data-editor-label="3 Columns">
          <div class="container">
            <div class="gcms-columns gcms-columns-3">
              <div class="gcms-column" data-gcms-column="1" data-drop-zone></div>
              <div class="gcms-column" data-gcms-column="2" data-drop-zone></div>
              <div class="gcms-column" data-gcms-column="3" data-drop-zone></div>
            </div>
          </div>
        </section>`
    },
    {
      id: 'columns-4',
      category: 'layout',
      title: '4 Columns',
      description: 'Four equal columns — drag widgets or elements inside',
      html: `
        <section class="section-bg gcms-custom-block" data-block-type="columns" data-editor-template="columns-4" data-gcms-custom="1" data-editor-label="4 Columns">
          <div class="container">
            <div class="gcms-columns gcms-columns-4">
              <div class="gcms-column" data-gcms-column="1" data-drop-zone></div>
              <div class="gcms-column" data-gcms-column="2" data-drop-zone></div>
              <div class="gcms-column" data-gcms-column="3" data-drop-zone></div>
              <div class="gcms-column" data-gcms-column="4" data-drop-zone></div>
            </div>
          </div>
        </section>`
    }
  ]
};
