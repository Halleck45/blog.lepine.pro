module Jekyll
  class TagIndex < Page
    def initialize(site, base, dir, tag)
      @site = site
      @base = base
      @dir = dir
      @name = 'index.html'
      self.process(@name)
      self.read_yaml(File.join(base, '_layouts'), 'tag_index.html')
      self.data['tag'] = tag
      tag_title_prefix = site.config['tag_title_prefix'] || 'Posts Tagged &ldquo;'
      tag_title_suffix = site.config['tag_title_suffix'] || '&rdquo;'
      self.data['title'] = "#{tag_title_prefix}#{tag}#{tag_title_suffix}"
    end
  end

  class TagGenerator < Generator
    safe true

    def generate(site)
      if site.layouts.key? 'tag_index'
        dir = site.config['tag_dir'] || 'tag'
        site.tags.keys.each do |tag|
          slug = slugify(tag)
          write_tag_index(site, File.join(dir, slug), tag)
        end
      end
    end

    def write_tag_index(site, dir, tag)
      index = TagIndex.new(site, site.source, dir, tag)
      index.render(site.layouts, site.site_payload)
      index.write(site.dest)
      site.pages << index
    end

    private

    def slugify(tag)
      tag.strip.downcase
        .gsub('é', 'e').gsub('è', 'e').gsub('ê', 'e').gsub('ë', 'e')
        .gsub('à', 'a').gsub('â', 'a').gsub('ä', 'a')
        .gsub('ù', 'u').gsub('û', 'u').gsub('ü', 'u')
        .gsub('î', 'i').gsub('ï', 'i')
        .gsub('ô', 'o').gsub('ö', 'o')
        .gsub('ç', 'c')
        .gsub(/[^a-z0-9\-]/, '-')
        .gsub(/-+/, '-')
        .gsub(/^-|-$/, '')
    end
  end
end
