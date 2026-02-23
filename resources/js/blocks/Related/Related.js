/**
 * Media Image Block
 */

//require('./Related.css').toString();
import './Related.css';

class Related {
    model_types = {
        post_page: 'Pages',
        post_news: 'News',
        post_download: 'Downloads',
        post_publication: 'Publications',
        post_announcement: 'Announcements',
        post_job: 'Jobs',
        post_galleries: 'Galleries',
        post_blog: 'Blog Posts',
    };

    constructor({data, api, config}) {
        this.api = api;
        this.config = config || {};
        this.data = data;
        this.data = {
            title: data.title || '',
            model: data.model || '',
            num_posts: data.num_posts || 2
        };

        this.wrapper = undefined;
    }

    static get toolbox() {
        return {
            title: 'Related Posts',
            icon: '<svg width="23" height="15" viewBox="0 0 23 15" xmlns="http://www.w3.org/2000/svg"><path class="fil0" d="M18 5l-2 1c0,1 0,1 0,1 0,1 0,1 0,2l2 1c0,-1 1,-1 2,-1 1,0 3,1 3,3 0,2 -2,3 -3,3 -2,0 -3,-1 -3,-3 0,0 0,0 0,-1l-2 -1c-1,1 -2,2 -4,2 -1,0 -2,-1 -3,-2l-2 1c0,1 0,1 0,1 0,2 -1,3 -3,3 -1,0 -3,-1 -3,-3 0,-2 2,-3 3,-3 1,0 2,0 2,1l2 -1c0,-1 0,-1 0,-2 0,0 0,0 0,-1l-2 -1c0,1 -1,1 -2,1 -1,0 -3,-1 -3,-3 0,-2 2,-3 3,-3 2,0 3,1 3,3 0,0 0,0 0,1l2 1c1,-1 2,-2 3,-2 2,0 3,1 4,2l2 -1c0,-1 0,-1 0,-1 0,-2 1,-3 3,-3 1,0 3,1 3,3 0,2 -2,3 -3,3 -1,0 -2,0 -2,-1z"/></svg>'
        };
    }

    /**
     * Automatic sanitize config
     */
    static get sanitize() {
        return {
            title: false,
            model: false,
            num_posts: false, // disallow HTML
        }
    }

    render() {
        this.wrapper = document.createElement('div');
        this.wrapper.classList.add('related');

        const title = document.createElement('input');
        title.classList.add(this.api.styles.input);
        title.classList.add('title-input');
        title.value = this.data.title || '';
        title.placeholder = 'Enter related posts title here...';
        this.wrapper.appendChild(title);

        const model_wrapper = document.createElement('div');
        model_wrapper.classList.add('model-wrapper');

        const model = document.createElement('select');
        model.classList.add(this.api.styles.input);
        model_wrapper.appendChild(model);

        let opt = document.createElement('option');
        opt.appendChild(document.createTextNode('Select a post type..'));
        opt.value = '';
        model.appendChild(opt);

        for (let model_type in this.model_types) {
            let model_type_name = this.model_types[model_type];

            opt = document.createElement('option');
            opt.appendChild(document.createTextNode(model_type_name));
            opt.value = model_type;
            model.appendChild(opt)
        }

        model.value = this.data.model || '';

        const num_posts = document.createElement('input');
        num_posts.classList.add(this.api.styles.input);
        num_posts.classList.add('num-posts-input');
        num_posts.value = this.data.num_posts || '';
        num_posts.placeholder = 'Number of posts to display';
        model_wrapper.appendChild(num_posts);

        this.wrapper.appendChild(model_wrapper);

        return this.wrapper;
    }

    save(blockContent) {
        const model = blockContent.querySelector('select');
        const title = blockContent.querySelector('.title-input');
        const num_posts = blockContent.querySelector('.num-posts-input');

        return Object.assign(this.data, {
            title: title.value || '',
            num_posts: num_posts.value || '',
            model: model.value || ''
        });
    }

    validate(savedData) {
        if (!(savedData.title.trim() || savedData.model.trim())) {
            return false;
        }

        if (!this.model_types.hasOwnProperty(savedData.model)) {
            return false;
        }

        if (savedData.num_posts != parseInt(savedData.num_posts) || savedData.num_posts <= 0) {
            return false;
        }

        return true;
    }
}

export default Related;
