export default {
    computed: {
        activeObject() {
            if (this.pageData.viewData === 'gallery') {
                return _.get(this.pageData.gallery, this.pageData.activePath)
            } else if (this.pageData.viewData === 'tags') {
                return _.get(this.pageData.tags, this.pageData.activePath)
            } else {
                return this.pageData.searchedImages
            }
        },
    },
    methods: {
        loadActiveCategoryImages(force = false) {
            if (this.pageData.activePath && this.pageData.viewData === 'gallery') {
                let activeCategory = _.get(this.pageData.gallery, this.pageData.activePath)
                if (!activeCategory) {
                    activeCategory = this.pageData.gallery[0]
                }

                if (activeCategory) {
                    this.loadCategoryImages(activeCategory, force)
                }
            }
        },
        loadCategoryImages(category, force = false) {
            let hasMore = category.hasMore

            if (hasMore === undefined) {
                hasMore = (category.images?.length || 0) < (category.images_count || 0)
            }

            if (force || (hasMore && !this.pageData.loadingCategories.includes(category.id))) {

                let page = Math.floor((category.images?.length || 0) / 30) + 1

                if (force) {
                    page = 1
                }

                this.pageData.loadingCategories.push(category.id)

                axios.get(route('merchant.image.category.images', {
                    category_id: category.id,
                    user_id: this.$page.props.auth.user.id,
                    status: this.pageData.statusFilter,
                    page: page,
                    orderBy: this.pageData.selectedOrderBy
                })).then(res => {
                    if (res.data.success) {
                        category.hasMore = res.data.has_more

                        if (!category.images || force) {
                            category.images = []
                        }

                        category.images = category.images.concat(res.data.images)

                        this.pageData.loadingCategories = this.pageData.loadingCategories.filter(id => id !== category.id)

                        if (this.pageData.activePath) {
                            const categoryPath = this.pageData.activePath.split('.')[0]
                            const category = _.get(this.pageData.gallery, categoryPath)
                            if (category?.children?.length) {
                                category.open = true
                            }
                        }
                    }
                })
            }
        },
        loadActiveTagImages(force = false) {
            if (this.pageData.activePath && this.pageData.viewData === 'tags') {
                let activeTag = _.get(this.pageData.tags, this.pageData.activePath)
                if (!activeTag) {
                    activeTag = this.pageData.tags[0]
                }

                if (activeTag) {
                    this.loadTagImages(activeTag, force)
                }
            }
        },
        loadTagImages(tag, force = false) {
            let hasMore = tag.hasMore

            if (hasMore === undefined) {
                hasMore = (tag.images?.length || 0) < (tag.user_images_count || 0)
            }

            if (force || (hasMore && !this.pageData.loadingTags.includes(tag.id))) {

                let page = Math.floor((tag.images?.length || 0) / 30) + 1

                if (force) {
                    page = 1
                }

                this.pageData.loadingTags.push(tag.id)

                axios.get(route('merchant.image.tag.images', {
                    tag_id: tag.id,
                    user_id: this.$page.props.auth.user.id,
                    status: this.pageData.statusFilter,
                    page: page
                }))
                    .then(res => {
                        if (res.data.success) {
                            tag.hasMore = res.data.has_more

                            if (!tag.images || force) {
                                tag.images = []
                            }

                            tag.images = tag.images.concat(res.data.images)

                            this.pageData.loadingTags = this.pageData.loadingTags.filter(id => id !== tag.id)
                        }
                    })
            }
        },
        loadActiveImages(force = false) {
            this.loadActiveCategoryImages(force)
            this.loadActiveTagImages(force)
        },
        async reloadGallery() {
            await axios.get(route('merchant.image.gallery.get', {
                user_id: this.$page.props.auth.user.id,
            })).then(res => {
                if (res.data.success) {
                    this.pageData.gallery = res.data.gallery
                    this.loadActiveCategoryImages(true)
                }
            })
        },
        async reloadTags() {
            await axios.get(route('merchant.image.tags.get', {
                user_id: this.$page.props.auth.user.id,
            })).then(res => {
                if (res.data.success) {
                    this.pageData.tags = res.data.tags
                    this.loadActiveTagImages(true)
                }
            })
        },
        async reloadAll() {
            await axios.get(route('merchant.image.reload', {
                user_id: this.$page.props.auth.user.id,
            })).then(res => {
                if (res.data.success) {
                    this.pageData.tags = res.data.tags
                    this.pageData.gallery = res.data.gallery
                }
            })
        },
        searchImages(force = false) {
            this.pageData.loadingImages = true

            let page = Math.floor((this.pageData.searchedImages.images?.length) / 30) + 1

            if (force) {
                page = 1
            }

            axios.get(route('merchant.image.search', {
                user_id: this.$page.props.auth.user.id,
                search: this.pageData.searchImage,
                status: this.pageData.statusFilter,
                page: page
            })).then(res => {
                this.pageData.loadingImages = false
                if (res.data.success) {

                    if (force || !this.pageData.searchedImages.images) this.pageData.searchedImages.images = [];

                    this.pageData.searchedImages.images_count = res.data.images.total;
                    this.pageData.searchedImages.images.push(...res.data.images.data);
                }
            })
        },
        loadImages() {
            if (this.pageData.viewData === 'gallery') {
                this.loadActiveCategoryImages()
            } else if (this.pageData.viewData === 'tags') {
                this.loadActiveTagImages()
            } else {
                this.searchImages()
            }
        },
        setTags(tags) {
            this.pageData.tags = tags
        },
        setGallery(gallery) {
            this.pageData.gallery = gallery
        },
        validatorCategoryName(value) {
            const hasSameCategory = this.pageData.gallery.some(ct => {
                if (ct.name === value) {
                    return true
                }

                if (ct.children) {
                    return ct.children.some(c => c.name === value)
                }

                return false
            })

            if (hasSameCategory) {
                return 'Category name already exists.'
            }

            return true
        }
    }
}
