M.peerreview = {
    numValues: 0,
    totalMarks: 0,
    rewardMarks: 0,

    initMarkSummary: function(YUIObject, total, reward) {
        this.totalMarks = total;
        this.rewardMarks = 2*reward;

        while(document.getElementById('id_value_'+this.numValues) != null) {
            if(document.getElementById('id_value_'+this.numValues).value=='') {
                document.getElementById('id_value_'+this.numValues).value = '0';
            }
            this.numValues++;
        }
        this.updateTotal();
    },

    updateTotal: function() {
        var sum = 0.0;
        var i;

        for(i=0; i<this.numValues; i++) {
            sum += parseFloat(document.getElementById('id_value_'+i).value);
        }
        if(isNaN(sum)) {
            document.getElementById('totalOfValues').innerHTML = '<span style=\"color:red\">'+M.str.peerreview.nonnumericcriterionvalue+'</span>';
            document.getElementById('totalOfMarksAbove').innerHTML = '<span style=\"color:red\">'+M.str.peerreview.nonnumericcriterionvalue+'</span>';
        }
        else {
            document.getElementById('totalOfValues').innerHTML = sum;
            document.getElementById('totalOfMarksAbove').innerHTML = sum+this.rewardMarks;
            document.getElementById('peerReviewDifference').innerHTML = '<span style=\"'+(this.totalMarks-this.rewardMarks-sum==0?'color:green;background:#e0ffe0;':'color:red;background:#ffe0e0;')+'\">'+(this.totalMarks-this.rewardMarks-sum)+'</span>';
        }
    },

    initHiddenDescription: function() {
        try{
            document.getElementById('hiddenDescription').style.display='none';
            document.getElementById('assignmentDescription').style.display='none';
            document.getElementById('showDescription').style.display='block';
            document.getElementById('hideDescription').style.display='block';
        }catch (err) {}


    },

    showDescription: function () {
        document.getElementById('hiddenDescription').style.display='block';
        document.getElementById('showDescription').style.display='none';
    },

    hideDescription: function () {
        document.getElementById('hiddenDescription').style.display='none';
        document.getElementById('showDescription').style.display='block';
    },

    showOnlineSubmission: function () {
        document.getElementById('hiddenOnlineSubmission').style.display='block';
        document.getElementById('showOnlineSubmission').style.display='none';
        this.enableReviewButton();
    },
    hideOnlineSubmission: function () {
        document.getElementById('hiddenOnlineSubmission').style.display='none';
        document.getElementById('showOnlineSubmission').style.display='block';
    },
    setNextPR: function (nextid){
        document.getElementById('submitform').mode.value='next';
        document.getElementById('submitform').userid.value=nextid;
    },

    saveNextPR: function (userid, nextid){
        document.getElementById('submitform').mode.value='saveandnext';
        document.getElementById('submitform').userid.value=nextid;
        document.getElementById('submitform').saveuserid.value=userid;
    },

    setSavePrevPR: function (){
        document.getElementById('submitform').savePrev.value='1';
    },

    allowSavePrev: function (){
        document.getElementById('savepreexistingonly').disabled=false;
    },

    allowSaveNew: function (){
        document.getElementById('savenew').disabled=false;
        if(document.getElementById('saveandnext')) {
            document.getElementById('saveandnext').disabled=false;
        }
    },

    initModerationButtons: function() {
        if(savepreexistingonly = document.getElementById('savepreexistingonly')) {
            savepreexistingonly.disabled = true;
        }
        document.getElementById('savenew').disabled = true;
        if(saveandnext = document.getElementById('saveandnext')) {
            saveandnext.disabled = true;
        }
    },

    checkComment: function() {
        if(document.getElementById('comment').value == '') {
            alert(M.str.peerreview.nocommentalert);
            document.getElementById('comment').focus();
            return false;
        }
        M.core_formchangechecker.set_form_submitted();
        return true;
    },

    initReviewForm: function() {
        var form = document.getElementById('peerreviewform');
        var elements = form.elements;
        for (var i = 0; i < elements.length; i++) {
            elements[i].disabled = true;
        }
    },

    enableReviewForm: function() {
        var form = document.getElementById('peerreviewform');
        var elements = form.elements;
        for (var i = 0; i < elements.length; i++) {
            elements[i].disabled = false;
        }
    },

    enableReviewButton: function() {
        var button = document.getElementById('idbuttoncontinuereview');
        button.disabled = false;
    },

    highlight: function(ids, colour) {
        var elements;
        var element;

        if(ids.length == 0) {
            return;
        }
        elements = ids.split(',');
        for (var i = 0; i < elements.length; i++) {
            element = document.getElementById(elements[i]);
            if(element) {
                element.style.background = colour;
            }
        }
    },
};
