module Jekyll
  module TagSlugFilter
    def tagslug(tag)
      tag.to_s.strip.downcase
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

Liquid::Template.register_filter(Jekyll::TagSlugFilter)
