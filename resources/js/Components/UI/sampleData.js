/*
 * Sample data for the UI pages, ported from the ica-guest static site.
 *
 * Shapes mirror the mobile app's Dart models (AuthorCompound, SeriesCompound,
 * Sermon, …) so phase two can pass real data from the controllers as page
 * props with no template changes — each UI page falls back to these arrays
 * only when the matching prop isn't provided. Dates are epoch milliseconds.
 */
const ts = (s) => new Date(s).getTime();

/* ---- AuthorCompound[] ---- (suffix is the honorific shown before the name, as in the console's AuthorResource) */
export const MINISTERS = [
    { id: 1, avatar: '', coverImage: null, name: 'Enson M. Lwesya', title: 'Senior Pastor', suffix: 'Rev. Dr.', slug: 'enson-lwesya', sermonCount: 184,
        biography: 'Founder and senior pastor of ICA, with a heart for raising disciples and equipping the Church for every good work.' },
    { id: 2, avatar: '', coverImage: null, name: 'Jerry Zimba', title: 'Cell Pastor', suffix: 'Ps.', slug: 'jerry-zimba', sermonCount: 64,
        biography: 'Oversees the cell ministry, shepherding small groups across the city into deeper community and growth.' },
    { id: 3, avatar: '', coverImage: null, name: 'Joel Phiri', title: 'Elder', suffix: 'Ps.', slug: 'joel-phiri', sermonCount: 37,
        biography: 'An elder devoted to teaching the Word with clarity and calling the congregation to freedom in Christ.' },
    { id: 4, avatar: '', coverImage: null, name: 'Victoria Manjawira', title: "Women's Fellowship Leader", suffix: 'Mrs', slug: 'victoria-manjawira', sermonCount: 8,
        biography: "Leads the women's fellowship, encouraging the sisters to walk boldly as ambassadors of Christ." },
    { id: 5, avatar: '', coverImage: null, name: 'Mike Mkali', title: 'Church Member', suffix: 'Mr', slug: 'mike-mkali', sermonCount: 5,
        biography: 'A faithful member who shares practical lessons on leadership drawn from Scripture.' },
    { id: 6, avatar: '', coverImage: null, name: 'Emmanuel Mtema', title: 'Church Member', suffix: 'Mr', slug: 'emmanuel-mtema', sermonCount: 4,
        biography: "Serves in the house of God and teaches on the beauty of belonging to God's family." },
];
const A = Object.fromEntries(MINISTERS.map((m) => [m.slug, m]));

/* ---- SeriesCompound[] ---- */
export const SERIES = [
    { id: 1, title: 'Gifts & Calling', slug: 'gifts-and-calling', duration: '8 weeks', sermonCount: 8, firstSermonDate: ts('2026-08-01'),
        description: 'Discovering, stirring up and deploying the gifts God has placed in every believer.' },
    { id: 2, title: 'The Unseen God', slug: 'the-unseen-god', duration: '5 weeks', sermonCount: 5, firstSermonDate: ts('2026-09-06'),
        description: 'Seeing the hand of a faithful God who is always at work, even when we cannot trace Him.' },
    { id: 3, title: 'Foundations', slug: 'foundations', duration: '10 weeks', sermonCount: 10, firstSermonDate: ts('2026-05-04'),
        description: 'The core doctrines that anchor a maturing faith, from repentance to the laying on of hands.' },
    { id: 4, title: 'The Journey', slug: 'the-journey', duration: '7 weeks', sermonCount: 7, firstSermonDate: ts('2026-07-05'),
        description: 'Lessons for the wilderness seasons — trusting God on the road between the promise and its fulfilment.' },
    { id: 5, title: 'Freedom in Christ', slug: 'freedom-in-christ', duration: '6 weeks', sermonCount: 6, firstSermonDate: ts('2026-04-06'),
        description: 'How the finished work of the cross breaks every curse and sets the captive free.' },
];
const S = Object.fromEntries(SERIES.map((s) => [s.slug, s]));

const P1 = 'Beloved, the Scriptures were written so that we might know the God who calls us by name. Today we open the Word not merely to gather information, but to be formed by the One who speaks.';
const P2 = 'Consider how patiently the Lord has worked through generations. What looks like an ending in our eyes is often the very place where God begins something new. He is never in a hurry, and He is never late.';
const P3 = 'So take heart. Whatever season you find yourself in, the same grace that carried the saints of old is available to you now. Receive it, walk in it, and let it overflow to those around you.';
const body = (extra) => [P1, extra || P2, P3];

/* ---- Sermon[] ---- */
export const SERMONS = [
    { id: 101, title: 'Hidden in Plain Sight', subtitle: 'Seeing the God who is always at work',
        videoUrl: 'https://youtu.be/ica-101', publishedAt: ts('2026-09-20'), createdAt: ts('2026-09-18'), updatedAt: ts('2026-09-20'),
        author: A['enson-lwesya'], series: S['the-unseen-god'], seriesId: 2, seriesTitle: 'The Unseen God',
        body: body("Reuben's story reminds us that being aware of our history helps us understand the present and pray with wisdom for the future. Nothing in Scripture is recorded by accident.") },
    { id: 102, title: 'Curses Can Be Reversed', subtitle: 'Redeemed from the curse of the law',
        videoUrl: 'https://youtu.be/ica-102', publishedAt: ts('2026-09-20'), createdAt: ts('2026-09-17'), updatedAt: ts('2026-09-20'),
        author: A['joel-phiri'], series: S['freedom-in-christ'], seriesId: 5, seriesTitle: 'Freedom in Christ',
        body: body('Galatians 3:13 declares that Christ has redeemed us from the curse of the law, becoming a curse for us, so that in Him every curse can be broken and every blessing restored.') },
    { id: 103, title: 'Greater Works', subtitle: null,
        videoUrl: 'https://youtu.be/ica-103', publishedAt: ts('2026-09-13'), createdAt: ts('2026-09-11'), updatedAt: ts('2026-09-13'),
        author: A['jerry-zimba'], series: S['gifts-and-calling'], seriesId: 1, seriesTitle: 'Gifts & Calling',
        body: body('Jesus said that those who believe would do greater works. The invitation is not to impressive activity but to faith that partners with the Spirit.') },
    { id: 104, title: 'Deploy Your Gifts', subtitle: 'From the pew to the harvest field',
        videoUrl: 'https://youtu.be/ica-104', publishedAt: ts('2026-09-13'), createdAt: ts('2026-09-10'), updatedAt: ts('2026-09-13'),
        author: A['enson-lwesya'], series: S['gifts-and-calling'], seriesId: 1, seriesTitle: 'Gifts & Calling',
        body: body('Every believer has been entrusted with something for the good of the body. Gifts left buried help no one; gifts deployed change the world.') },
    { id: 105, title: "Value God's Trust", subtitle: null,
        videoUrl: null, publishedAt: ts('2026-09-06'), createdAt: ts('2026-09-04'), updatedAt: ts('2026-09-06'),
        author: A['enson-lwesya'], series: null, seriesId: null, seriesTitle: null,
        body: body('To be trusted by God is a sacred stewardship. Faithfulness in little is the doorway to being entrusted with much.') },
    { id: 106, title: 'Managing Frustrations on the Journey', subtitle: 'Grace for the wilderness seasons',
        videoUrl: 'https://youtu.be/ica-106', publishedAt: ts('2026-08-30'), createdAt: ts('2026-08-28'), updatedAt: ts('2026-08-30'),
        author: A['jerry-zimba'], series: S['the-journey'], seriesId: 4, seriesTitle: 'The Journey',
        body: body("The road between the promise and its fulfilment is often long. Yet the wilderness is where God forms the character that the promised land requires.") },
    { id: 107, title: 'The Laying on of Hands', subtitle: null,
        videoUrl: null, publishedAt: ts('2026-08-22'), createdAt: ts('2026-08-20'), updatedAt: ts('2026-08-22'),
        author: A['enson-lwesya'], series: S['foundations'], seriesId: 3, seriesTitle: 'Foundations',
        body: body('Among the foundational teachings of the faith is the laying on of hands — a means of blessing, commissioning and impartation.') },
    { id: 108, title: 'Empowered Ambassadors', subtitle: 'Representing Christ in His character',
        videoUrl: null, publishedAt: ts('2026-07-19'), createdAt: ts('2026-07-17'), updatedAt: ts('2026-07-19'),
        author: A['victoria-manjawira'], series: null, seriesId: null, seriesTitle: null,
        body: body('We are ambassadors, and an ambassador carries the character of the one who sends them. Our conduct is our credential.') },
    { id: 109, title: 'House of God', subtitle: null,
        videoUrl: null, publishedAt: ts('2026-06-28'), createdAt: ts('2026-06-26'), updatedAt: ts('2026-06-28'),
        author: A['emmanuel-mtema'], series: null, seriesId: null, seriesTitle: null,
        body: body('There is a joy in belonging to the household of God. It is not a building we attend but a family we belong to.') },
];

/* ---- Community content ---- */
export const EVENTS = [
    { id: 1, title: 'Sunday Worship Service', date: ts('2026-09-21'), time: '9:00 – 11:30 AM', loc: 'Main Auditorium', tag: 'Weekly', desc: 'Gather with the whole church for worship, the Word and communion.' },
    { id: 2, title: 'Midweek Bible Study', date: ts('2026-09-24'), time: '6:00 – 7:30 PM', loc: 'Fellowship Hall', tag: 'Teaching', desc: 'A deeper dive into the current teaching series.' },
    { id: 3, title: 'Youth Praise Night', date: ts('2026-09-27'), time: '5:30 – 8:00 PM', loc: 'Youth Centre', tag: 'Youth', desc: 'An evening of praise, testimony and fellowship for the young people.' },
    { id: 4, title: 'Communion & Thanksgiving', date: ts('2026-10-03'), time: '9:00 AM', loc: 'Main Auditorium', tag: 'Special', desc: "A special service of thanksgiving and the Lord's table." },
    { id: 5, title: 'Baptism Sunday', date: ts('2026-10-12'), time: '10:00 AM', loc: 'Main Auditorium', tag: 'Ordinance', desc: 'Celebrating new believers taking the step of baptism.' },
];

/* ---- PrayerResource[] ---- (date · title · optional verses) */
export const PRAYER_POINTS = [
    { id: 1, date: ts('2026-09-27'), title: 'Praying for Nigeria', verses: 'Psalm 2:8; Matthew 9:38' },
    { id: 2, date: ts('2026-09-27'), title: 'Our cell groups', verses: 'Acts 2:46-47' },
    { id: 3, date: ts('2026-09-26'), title: 'Healing & breakthrough', verses: 'Isaiah 53:5' },
    { id: 4, date: ts('2026-09-25'), title: 'Wisdom for our leaders', verses: null },
    { id: 5, date: ts('2026-09-24'), title: 'Open doors for the gospel', verses: 'Colossians 4:3' },
    { id: 6, date: ts('2026-09-23'), title: 'Unity in the Body', verses: 'John 17:21' },
];

export const ANNOUNCEMENTS = [
    { id: 1, date: ts('2026-09-20'), title: "New members' class begins next Sunday", body: "If you're new to ICA, join us after the second service to learn our vision and how to get planted." },
    { id: 2, date: ts('2026-09-18'), title: 'Choir auditions open', body: 'The worship team is welcoming new voices. Speak to a team member to register.' },
    { id: 3, date: ts('2026-09-15'), title: 'Building fund update', body: "Thank you church! We've reached 81% of the September goal. Let's finish strong." },
];

export const BIRTHDAYS = [
    { name: 'Thokozani Kachingwe', date: ts('2026-09-20'), cell: 'Area 47 Cell' },
    { name: 'Grace Banda', date: ts('2026-09-22'), cell: 'Youth · Zone B' },
    { name: 'Ruth Chirwa', date: ts('2026-09-25'), cell: "Women's Fellowship" },
];

export const CELLS = [
    { id: 1, name: 'Area 47 Cell', leader: 'Ps. Joel Phiri', members: 32, day: 'Wednesdays', loc: 'Area 47, Sector 3' },
    { id: 2, name: "Women's Fellowship", leader: 'Victoria Manjawira', members: 48, day: 'Saturdays', loc: 'Fellowship Hall' },
    { id: 3, name: 'Youth · Zone A', leader: 'Grace Banda', members: 40, day: 'Fridays', loc: 'Youth Centre' },
    { id: 4, name: 'Area 25 Cell', leader: 'Emmanuel Mtema', members: 28, day: 'Thursdays', loc: 'Area 25, off Kenyatta Rd' },
    { id: 5, name: "Men's Fellowship", leader: 'Mike Mkali', members: 36, day: 'Saturdays', loc: 'Main Church annex' },
];

export const MEETINGS = [
    { cell: 'Area 47 Cell', date: ts('2026-09-24'), time: '6:00 PM', topic: 'Gifts & Calling — week 8' },
    { cell: 'Youth · Zone A', date: ts('2026-09-27'), time: '5:30 PM', topic: 'Praise night prep' },
    { cell: "Women's Fellowship", date: ts('2026-09-27'), time: '2:00 PM', topic: 'Empowered Ambassadors' },
    { cell: "Men's Fellowship", date: ts('2026-09-27'), time: '7:00 AM', topic: 'Prayer & breakfast' },
];

export const CELL_MEMBERS = [
    { id: 1, name: 'Thokozani Kachingwe', role: 'Member', cell: 'Area 47 Cell', joined: 'Mar 2019' },
    { id: 2, name: 'Yankho Mpesi', role: 'Member', cell: 'Area 47 Cell', joined: 'Feb 2023' },
    { id: 3, name: 'Joel Phiri', role: 'Cell Leader', cell: 'Area 47 Cell', joined: 'Sep 2016' },
    { id: 4, name: 'Ruth Chirwa', role: 'Intercessor', cell: 'Area 47 Cell', joined: 'Jun 2019' },
    { id: 5, name: 'Blessings Gondwe', role: 'Member', cell: 'Area 47 Cell', joined: 'Oct 2020' },
];

export const RESOURCES = [
    { title: 'Weekly sermon notes', kind: 'PDF', desc: "Fill-in outline for this Sunday's message.", size: '320 KB' },
    { title: 'Bible reading plan 2026', kind: 'PDF', desc: 'A chapter-a-day plan through the whole Bible.', size: '1.1 MB' },
    { title: "New believers' guide", kind: 'PDF', desc: 'First steps for following Jesus.', size: '640 KB' },
    { title: "Cell leaders' handbook", kind: 'PDF', desc: 'Everything to run a healthy cell group.', size: '2.4 MB' },
    { title: 'Giving & tithing FAQ', kind: 'Link', desc: 'Answers to common questions about giving.', size: '' },
];

/* ---- Giving ---- */
export const BANK_DETAILS = [
    { label: 'Bank', value: 'National Bank of Malawi' },
    { label: 'Account name', value: 'International Christian Assembly' },
    { label: 'Account number', value: '1004 5678 9012 3' },
    { label: 'Branch', value: 'Lilongwe Main' },
    { label: 'Swift / BIC', value: 'NBMAMWMW' },
];

export const MOBILE_MONEY = [
    { name: 'Airtel Money', num: '0991 22 44 10', merchant: 'ICA Church' },
    { name: 'TNM Mpamba', num: '0888 71 20 93', merchant: 'ICA Church' },
];

/* ---- Attendance ---- */
export const ATTENDANCE_FEATURED = {
    id: 'svc-2026-09-21', eyebrow: 'This Sunday · 21 Sep', title: 'Sunday Worship Service',
    detail: '9:00 AM · Main Auditorium. Tap to check in when you arrive.',
};

export const ATTENDANCE_STATS = { servicesAttended: 41, streak: 6, cellMeetings: 18 };

export const ATTENDANCE_SHEETS = [
    { id: 'svc-2026-09-21', title: 'Sunday Worship Service', detail: 'Sun 21 Sep · 9:00 AM · Main Auditorium' },
    { id: 'bs-2026-09-24', title: 'Midweek Bible Study', detail: 'Wed 24 Sep · 6:00 PM · Fellowship Hall' },
    { id: 'cell-2026-09-24', title: 'Area 47 Cell Meeting', detail: 'Wed 24 Sep · 6:00 PM · Area 47' },
];

export const ATTENDANCE_HISTORY = [
    { title: 'Sunday Worship Service', date: '14 Sep 2026', present: true },
    { title: 'Midweek Bible Study', date: '10 Sep 2026', present: true },
    { title: 'Sunday Worship Service', date: '7 Sep 2026', present: true },
    { title: 'Sunday Worship Service', date: '31 Aug 2026', present: false },
];

/* ---- Dashboard ---- */
// Registers that are open right now (RegisterResource with active = true).
export const LIVE_SERVICES = [
    { id: 'svc-2026-09-27-1', name: 'Sunday Worship · First Service', ministry: 'Main Church', time: '9:00 AM' },
    { id: 'svc-2026-09-27-youth', name: 'Youth Service', ministry: 'Youth Ministry', time: '9:30 AM' },
];

// The signed-in member's next cell meeting.
export const NEXT_CELL_MEETING = {
    cell: 'Area 47 Cell', topic: 'Gifts & Calling — week 9', date: ts('2026-09-30'), time: '6:00 PM',
    loc: 'Area 47, Sector 3', leader: 'Ps. Joel Phiri',
};

/* ---- Sermon reader ---- */
export const SEED_COMMENTS = [
    { name: 'Grace Banda', when: '2 days ago', text: 'This really ministered to me. Thank you Pastor for the reminder that God is always at work.' },
    { name: 'Emmanuel Mtema', when: '1 day ago', text: 'Sharing this with my cell group this week. So timely.' },
];
