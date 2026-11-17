import { defineConfig, passthroughImageService, sharpImageService } from 'astro/config'
import mdx from '@astrojs/mdx'
import tailwind from '@astrojs/tailwind'
import react from '@astrojs/react'
import sitemap from '@astrojs/sitemap'
import partytown from '@astrojs/partytown'
import rehypePrettyCode from 'rehype-pretty-code'
import { transformerCopyButton } from '@rehype-pretty/transformers'
import { SITE } from './src/config.ts'
import { remarkReadingTime } from './src/support/plugins.ts'
import { uploadAssetsToS3 } from './src/support/uploader.ts'

export default defineConfig({
    site: SITE.url,
    redirects: {
        // '/manual': '/manual/index', // Handled by [...slug].astro
    },
    image: {
        // If you don't want to optimize images during the BUILD process please set the ASTRO_IMAGE_OPTIMIZE environment variable to false
        // Please note that the environment value here is `string` type on Cloudflare Pages,
        // So please delete the environment variable directly if you want to disable the image optimization service
        service: (!!import.meta.env.ASTRO_IMAGE_OPTIMIZE || !!process.env.ASTRO_IMAGE_OPTIMIZE) ? sharpImageService() : passthroughImageService(),
    },
    integrations: [
        partytown(),
        mdx(),
        sitemap(),
        tailwind(),
        react(),
        // Slice builds (BLOG_SLICE) set SKIP_COMPRESS=1: post HTML is
        // harvested for KV mid-build and uniform compression would need a
        // full run; the deploy dist comes from a normal compressed build.
        ...(process.env.SKIP_COMPRESS ? [] : [(await import('@playform/compress')).default({
            // The bundled upstream manual is copied unchanged, not rebuilt with the site.
            Exclude: [(file) => file.includes('/manual/') || file.includes('/magazines/')],
            CSS: true,
            JavaScript: true,
            HTML: {
                'html-minifier-terser': {
                    collapseWhitespace: true,
                    minifyCSS: false, // enable this will cause the CopyButton not work
                    minifyJS: true,
                },
            },
            Image: false,
            SVG: true,
            Logger: 2,
        })]),
        uploadAssetsToS3(),
    ],
    markdown: {
        syntaxHighlight: false,
        rehypePlugins: [
            [rehypePrettyCode, {
                theme: {
                    light: 'github-light',
                    dark: 'github-dark-dimmed',
                },
                keepBackground: false,
                transformers: [
                    transformerCopyButton(
                        {
                            visibility: 'always',
                            feedbackDuration: 2500,
                        },
                    ),
                ],
            }],
        ],
        remarkPlugins: [remarkReadingTime],
    },
    devToolbar: {
        enabled: false,
    },
    prefetch: true,
    output: 'static',
    build: {
        // Specifies the directory in the build output where Astro-generated assets (bundled JS and CSS for example) should live.
        // see https://docs.astro.build/en/reference/configuration-reference/#buildassets
        assets: 'assets',
        // see https://docs.astro.build/en/reference/configuration-reference/#buildassetsprefix
        assetsPrefix: (!!import.meta.env.S3_ENABLE || !!process.env.S3_ENABLE) ? 'https://images.godruoyi.com/gblog' : '',
    },
})
