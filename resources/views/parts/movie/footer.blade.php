<footer class="app-footer">
    <div class="app-footer_info">
        <div class="container-xl">
            <div class="app-footer_inner clearfix">
                <dl class="app-footer_company">
                    <dt class="app-footer_brand">{{ seo_site_name() }}</dt>
                    <dd class="app-footer_contact">
                        <div class="contact__item">
                            合作：<span> {{ 'work@' . get_domain() }}</span>
                        </div>
                        <div class="contact__item">
                            邮箱：<span> {{ 'services@' . get_domain() }}</span>
                        </div>
                    </dd>
                </dl>
                <!-- <section class="sec_item company-prod">
                    <div><span class="section_title">网站地图</span></div>
                    @foreach (sitemap() as $word => $path)
                        <p><a href="{{ $path }}" class="text-a">{{ $word }}</a></p>
                    @endforeach
                </section> -->
                <section class="sec_item friendly-links">
                    <div><span class="section_title">友情链接</span></div>
                    @foreach (friend_links() as $linkInfo)
                        <p><a href="{{ $linkInfo['url'] ?? '' }}"
                                class="text-a">{{ $linkInfo['name'] ?? '' }}</a></p>
                    @endforeach
                </section>
            </div>
        </div>
    </div>
    <div class="app-footer_bottom">
        <div class="container-xl">
            <div class="statement">
                本站所有视频和图片均来自互联网收集而来，版权归原创者所有，本网站只提供web页面服务，并不提供资源存储，也不参与录制、上传。若本站收录的内容无意侵犯了贵司版权，请发邮件联系我们，我们会在3个工作日内删除侵权内容，谢谢。
            </div>
        </div>
    </div>
</footer>

@include('parts.js_for_footer')
