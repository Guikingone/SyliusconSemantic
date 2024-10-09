<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\DataFixtures;

use App\Entity\Comment;
use App\Entity\Post;
use App\Entity\Tag;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\String\AbstractUnicodeString;
use Symfony\Component\String\Slugger\SluggerInterface;
use function Symfony\Component\String\u;

final class AppFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly SluggerInterface $slugger,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $this->loadUsers($manager);
        $this->loadTags($manager);
        $this->loadPosts($manager);
    }

    private function loadUsers(ObjectManager $manager): void
    {
        foreach ($this->getUserData() as [$fullname, $username, $password, $email, $roles]) {
            $user = new User();
            $user->setFullName($fullname);
            $user->setUsername($username);
            $user->setPassword($this->passwordHasher->hashPassword($user, $password));
            $user->setEmail($email);
            $user->setRoles($roles);

            $manager->persist($user);
            $this->addReference($username, $user);
        }

        $manager->flush();
    }

    private function loadTags(ObjectManager $manager): void
    {
        foreach ($this->getTagData() as $name) {
            $tag = new Tag($name);

            $manager->persist($tag);
            $this->addReference('tag-'.$name, $tag);
        }

        $manager->flush();
    }

    private function loadPosts(ObjectManager $manager): void
    {
        foreach ($this->getPostData() as [$title, $slug, $summary, $content, $publishedAt, $author, $tags]) {
            $post = new Post();
            $post->setTitle($title);
            $post->setSlug($slug);
            $post->setSummary($summary);
            $post->setContent($content);
            $post->setPublishedAt($publishedAt);
            $post->setAuthor($author);
            $post->addTag(...$tags);

            foreach (range(1, 5) as $i) {
                /** @var User $commentAuthor */
                $commentAuthor = $this->getReference('john_user');

                $comment = new Comment();
                $comment->setAuthor($commentAuthor);
                $comment->setContent($this->getRandomText(random_int(255, 512)));
                $comment->setPublishedAt(new \DateTimeImmutable('now + '.$i.'seconds'));

                $post->addComment($comment);
            }

            $manager->persist($post);
        }

        $manager->flush();
    }

    /**
     * @return array<array{string, string, string, string, array<string>}>
     */
    private function getUserData(): array
    {
        return [
            // $userData = [$fullname, $username, $password, $email, $roles];
            ['Jane Doe', 'jane_admin', 'kitten', 'jane_admin@symfony.com', [User::ROLE_ADMIN]],
            ['Tom Doe', 'tom_admin', 'kitten', 'tom_admin@symfony.com', [User::ROLE_ADMIN]],
            ['John Doe', 'john_user', 'kitten', 'john_user@symfony.com', [User::ROLE_USER]],
        ];
    }

    /**
     * @return string[]
     */
    private function getTagData(): array
    {
        return [
            'lorem',
            'ipsum',
            'consectetur',
            'adipiscing',
            'incididunt',
            'labore',
            'voluptate',
            'dolore',
            'pariatur',
        ];
    }

    /**
     * @throws \Exception
     *
     * @return array<int, array{0: string, 1: AbstractUnicodeString, 2: string, 3: string, 4: \DateTimeImmutable, 5: User, 6: array<Tag>}>
     */
    private function getPostData(): array
    {
        $posts = [];

        foreach ($this->getMoviesCritics() as $title => $critic) {
            // $postData = [$title, $slug, $summary, $content, $publishedAt, $author, $tags, $comments];

            /** @var User $user */
            $user = $this->getReference(['jane_admin', 'tom_admin'][str_contains('a', $title) ? 0 : random_int(0, 1)]);

            $posts[] = [
                $title,
                $this->slugger->slug($title)->lower(),
                substr($critic, 0, 255),
                $critic,
                (new \DateTimeImmutable('now - '.random_int(1, 10).'days'))->setTime(random_int(8, 17), random_int(7, 49), random_int(0, 59)),
                // Ensure that the first post is written by Jane Doe to simplify tests
                $user,
                $this->getRandomTags(),
            ];
        }

        return $posts;
    }

    /**
     * @return string[]
     */
    private function getPhrases(): array
    {
        return [
            'Lorem ipsum dolor sit amet consectetur adipiscing elit',
            'Pellentesque vitae velit ex',
            'Mauris dapibus risus quis suscipit vulputate',
            'Eros diam egestas libero eu vulputate risus',
            'In hac habitasse platea dictumst',
            'Morbi tempus commodo mattis',
            'Ut suscipit posuere justo at vulputate',
            'Ut eleifend mauris et risus ultrices egestas',
            'Aliquam sodales odio id eleifend tristique',
            'Urna nisl sollicitudin id varius orci quam id turpis',
            'Nulla porta lobortis ligula vel egestas',
            'Curabitur aliquam euismod dolor non ornare',
            'Sed varius a risus eget aliquam',
            'Nunc viverra elit ac laoreet suscipit',
            'Pellentesque et sapien pulvinar consectetur',
            'Ubi est barbatus nix',
            'Abnobas sunt hilotaes de placidus vita',
            'Ubi est audax amicitia',
            'Eposs sunt solems de superbus fortis',
            'Vae humani generis',
            'Diatrias tolerare tanquam noster caesium',
            'Teres talis saepe tractare de camerarius flavum sensorem',
            'Silva de secundus galatae demitto quadra',
            'Sunt accentores vitare salvus flavum parses',
            'Potus sensim ad ferox abnoba',
            'Sunt seculaes transferre talis camerarius fluctuies',
            'Era brevis ratione est',
            'Sunt torquises imitari velox mirabilis medicinaes',
            'Mineralis persuadere omnes finises desiderium',
            'Bassus fatalis classiss virtualiter transferre de flavum',
        ];
    }

    private function getRandomText(int $maxLength = 255): string
    {
        $phrases = $this->getPhrases();
        shuffle($phrases);

        do {
            $text = u('. ')->join($phrases)->append('.');
            array_pop($phrases);
        } while ($text->length() > $maxLength);

        return $text;
    }

    /**
     * @throws \Exception
     *
     * @return array<Tag>
     */
    private function getRandomTags(): array
    {
        $tagNames = $this->getTagData();
        shuffle($tagNames);
        $selectedTags = \array_slice($tagNames, 0, random_int(2, 4));

        return array_map(function ($tagName): Tag {
            /** @var Tag $tag */
            $tag = $this->getReference('tag-'.$tagName);

            return $tag;
        }, $selectedTags);
    }

    private function getMoviesCritics(): array
    {
        return [
            'Taxi Driver' => <<<'MARKDOWN'
            Martin Scorsese has always been known for his breathtaking and complex films, but 'Taxi Driver' is arguably one of his masterpieces. Released in 1976, this New York-based neo-noir plunges us into the bleak and oppressive world of Manhattan's streets during the 1970s.

            Robert De Niro's performance as Travis Bickle, a solitary and desperate taxi driver, is incredibly convincing. He brings to life the depths of Travis's moral and psychological distress without ever resorting to sentimental language. The nuance in De Niro's
            portrayal of Travis Bickle sparks reflection on modern societies.
            
            Scorsese's direction is also noteworthy. He employs specific angles and shots to create an intimate connection with the viewer, plunging us directly into the depths of Travis's mind. The song "You've Lost That Lovin' Feelin'" by Archie Bell & The Drells is also
            used perfectly to highlight the rebellion of New York City's streets.
            
            The film primarily explores themes of exhaustion, alienation, and despair. Travis Bickle represents a form of anarchism in Manhattan's streets, seeking to get rid of the established powers to find his own path. This idea is particularly relevant in today's
            cultural context where revolution is seen as a viable option.
            
            In terms of cultural critique, 'Taxi Driver' is regarded as a masterpiece of American New Wave cinema. It has influenced numerous filmmakers and writers, including Quentin Tarantino. The psychological tension between Travis Bickle and the woman of New York
            politician (Jodie Foster) is particularly intriguing.
            
            The way Scorsese addresses themes of homosociality in the film is also worth noting. The character of Bickle is ambiguous, can be seen as either gay or simply someone who has sex with a male prostitute. This ambiguity creates psychological tension that sparks
            reflection on social norms.
            
            Today, the film continues to hold significant cultural importance. It remains a valuable document of America's 1970s spirit and has marked filmmakers and writers in its wake.
            
            It is also worth noting that the film 'Taxi Driver' was criticized for its depiction of violence and misogyny. Some argued that the film glorified violence and misogyny, while others claimed it was a critique of these practices.
            
            In any case, 'Taxi Driver' remains a film that continues to evoke reflection on modern societies. It reminds us that revolution and insurrection are necessary to break social norms and create new possibilities.
            MARKDOWN,
            'La La Land' => <<<'MARKDOWN'
            Damien Chazelle's 'La La Land' is a cinematic masterpiece that has captured the hearts of audiences worldwide. Released in 2016, this modern romantic musical tells the story of two aspiring artists, Sebastian and Mia, as they navigate love, loss, and their
            passions in Los Angeles.

            Ryan Gosling and Emma Stone deliver outstanding performances as the lead characters, bringing to life a nuanced exploration of creativity, ambition, and the human condition. The film's use of music, dance, and visual aesthetics is nothing short of breathtaking,
            with a soundtrack that features a mix of classic jazz and modern pop.

            One of the standout aspects of 'La La Land' is its thematic complexity. Chazelle explores the highs and lows of pursuing one's dreams, as well as the tension between artistic expression and commercial success. The film's portrayal of Sebastian, a jazz pianist
            who is torn between his love for traditional music and the allure of fame, resonates deeply with audiences.

            The chemistry between Gosling and Stone is undeniable, and their performances are elevated by the film's witty dialogue and comedic moments. The supporting cast, including John Legend and Rosemarie DeWitt, add depth and nuance to the story.

            Visually, 'La La Land' is a work of art. Chazelle's direction is meticulous, with a keen eye for detail that captures the essence of Los Angeles's iconic landmarks and hidden corners. The film's color palette is particularly noteworthy, with a mix of warm,
            golden tones and cool, pastel hues that evoke the city's dualities.

            However, some critics have argued that 'La La Land' is overly sentimental and nostalgic, with a focus on romance and nostalgia that overshadows its thematic complexity. Others have pointed out the film's problematic portrayal of women and minorities in
            Hollywood, citing the underrepresentation and marginalization of these groups.

            While these criticisms are valid, I believe that 'La La Land' remains a powerful and poignant film that deserves to be celebrated. Its themes of creativity, perseverance, and love are universal and relatable, and its stunning visuals and music will leave
            audiences spellbound. Ultimately, 'La La Land' is a film that will continue to resonate with viewers long after the credits roll.

            In conclusion, 'La La Land' is a cinematic masterpiece that deserves recognition as one of the best films of the 2010s. Its thematic complexity, outstanding performances, and stunning visuals make it a must-see for anyone who loves music, dance, or simply great
            storytelling.
            MARKDOWN,
            'Grand Budapest Hotel' => <<<'MARKDOWN'
            Wes Anderson's 'The Grand Budapest Hotel' is a visual feast that has captivated audiences with its intricate storytelling, stunning costumes, and vibrant colors. Released in 2014, this whimsical comedy-drama follows the adventures of Gustave H, a legendary
            concierge at the famous Grand Budapest Hotel in the fictional Republic of Zubrowka.

            Ralph Fiennes delivers a tour-de-force performance as Gustave, bringing to life a charismatic and eccentric character with wit, charm, and a touch of madness. The supporting cast, including Tony Revolori, F. Murray Abraham, and Willem Dafoe, add depth and humor
            to the story, while Saoirse Ronan shines as Agatha, a young pastry chef who becomes embroiled in Gustave's adventures.

            Anderson's direction is, as always, meticulous and inventive, with a keen eye for detail that brings the Grand Budapest Hotel to life. The film's use of color is particularly noteworthy, with a palette that shifts from soft pastels to vibrant primaries,
            capturing the hotel's eccentric charm.

            One of the standout aspects of 'The Grand Budapest Hotel' is its thematic complexity. Anderson explores themes of identity, morality, and the passage of time, weaving together a narrative that is both fantastical and grounded in reality. The film's portrayal of
            Gustave's complicated relationship with his adopted son, Zero, adds a layer of depth to the story, while the hotel's cast of characters provides a rich tapestry of supporting roles.

            However, some critics have argued that 'The Grand Budapest Hotel' is overly mannered and indulgent, with Anderson's signature style sometimes bordering on excess. Others have pointed out the film's reliance on nostalgia and kitsch, which can feel heavy-handed at
            times.

            While these criticisms are valid, I believe that 'The Grand Budapest Hotel' remains a masterpiece of contemporary cinema. Its visual beauty, intricate storytelling, and memorable characters make it a must-see for fans of Anderson's work.

            In particular, the film's use of cinematography is noteworthy. The camera work is often stunning, with sweeping shots of the hotel's corridors and gardens that evoke a sense of wonder and enchantment. The use of practical effects also adds to the film's visual
            charm, from the intricate set pieces to the elaborate costumes.

            Ultimately, 'The Grand Budapest Hotel' is a film that rewards multiple viewings and has become a cult classic among audiences. Its whimsical humor, stunning visuals, and memorable characters make it a must-see for anyone who loves great storytelling and
            cinematic artistry.
            MARKDOWN,
            'Goldfinger' => <<<'MARKDOWN'
            Goldfinger, the third James Bond film starring Sean Connery, is a masterclass in style, sophistication, and intrigue. Directed by Guy Hamilton, this iconic spy thriller is widely regarded as one of the greatest Bond films of all time, and for good reason.

            The plot follows Bond (Connery) as he investigates Auric Goldfinger (Gert Fröbe), a wealthy businessman with a secret plan to rob Fort Knox using the atomic bomb. Along the way, Bond must navigate a complex web of villains, double agents, and seductive women,
            all while confronting his own dark past.

            One of the standout aspects of Goldfinger is its style. The film's use of color is particularly noteworthy, with a palette that shifts from sleek metallic tones to vibrant reds and oranges. The production design is equally impressive, with detailed sets and
            costumes that evoke the glamour and excess of 1960s London.

            Connery shines as Bond, bringing his signature blend of charm, wit, and danger to the role. His chemistry with Pussy Galore (Honor Blackman) is particularly memorable, and their rivalry becomes a highlight of the film.

            The supporting cast is equally impressive, with standout performances from Gert Fröbe as Goldfinger and Alan Tudyk as Oddjob, the henchman with a penchant for gardening. The film's villainy is also noteworthy, with Goldfinger's plan to rob Fort Knox being both
            ridiculous and menacingly effective.

            However, some critics have argued that Goldfinger relies too heavily on formulaic Bond tropes, with a plot that follows a predictable arc from start to finish. Others have pointed out the film's over-reliance on gadgets, with Bond's wristwatch laser being
            particularly egregious.

            While these criticisms are valid, I believe that Goldfinger remains a must-see for any Bond fan or fan of spy thrillers in general. Its style, sophistication, and intrigue make it a timeless classic that continues to hold up today.

            In particular, the film's use of cinematography is noteworthy. The camera work is often stylish, with sweeping shots of London's skyline and clever uses of light and shadow. The film's pacing is also well-balanced, with a steady stream of action, suspense, and
            humor that keeps the viewer engaged from start to finish.

            Ultimately, Goldfinger is a film that has aged remarkably well. Its style, sophistication, and intrigue make it a timeless classic that continues to delight audiences to this day. If you haven't seen it before, do yourself a favor and experience Bond like never
            before.
            MARKDOWN,
            'Amélie Poulain' => <<<'MARKDOWN'
            Amélie, the whimsical and charming French romantic comedy directed by Jean-Pierre Jeunet, is a cinematic treasure that has captured the hearts of audiences worldwide. Based on the novel "My Neighbor Joelle" by Claude Clerc, this delightful film follows the
            story of Amélie Poulain (Audrey Tautou), a shy and eccentric young woman living in Paris.

            The plot centers around Amélie's desire to help others find happiness, which leads her to secretly improve the lives of those around her. Along the way, she meets Nino Quincampoix (Mathieu Kassovitz), a quiet and brooding musician who becomes her unlikely love
            interest. As their relationship blossoms, Amélie must confront her own fears and insecurities in order to reveal her true feelings.

            One of the standout aspects of Amélie is its visual charm. The film's use of color, lighting, and production design is particularly noteworthy, with a vibrant palette that reflects the essence of Parisian culture. From the picturesque streets of Montmartre to
            the quirky boutiques of Le Marais, every frame is a feast for the eyes.

            Audrey Tautou shines as Amélie, bringing a perfect blend of vulnerability and wit to the role. Her chemistry with Mathieu Kassovitz is undeniable, and their romance becomes a highlight of the film. The supporting cast is equally impressive, with standout
            performances from Dominique Pinon as Amélie's eccentric neighbor and Emmanuelle Devos as her nosy but lovable aunt.

            The film's themes of kindness, empathy, and self-discovery are beautifully woven throughout the narrative, making it a cinematic experience that is both entertaining and thought-provoking. Jeunet's direction is masterful, with a keen eye for detail that brings
            the world of Amélie to life in a way that feels both authentic and fantastical.

            However, some critics have argued that Amélie relies too heavily on sentimentalism, with a narrative that can feel overly simplistic at times. Others have pointed out the film's lack of conflict or tension, which can make it feel a bit too lighthearted for some
            viewers.

            While these criticisms are valid, I believe that Amélie remains a timeless classic that continues to delight audiences worldwide. Its visual charm, charming performances, and uplifting themes make it a cinematic experience that is both entertaining and
            enriching.

            In particular, the film's use of music is noteworthy. The soundtrack features a delightful selection of French pop classics, including "A Little Bit of Love" and "Comme d'habitude", which perfectly capture the essence of Amélie's whimsical world. The film's
            score, composed by Yann Tiersen, is equally enchanting, with a beautiful blend of piano and accordion that adds to the overall sense of wonder and magic.

            Ultimately, Amélie is a film that will leave you feeling uplifted, inspired, and maybe even a little bit changed. Its visual charm, charming performances, and uplifting themes make it a cinematic experience that is not to be missed.
            MARKDOWN,
            'Interstellar' => <<<'MARKDOWN'
            Interstellar, the visually stunning and intellectually stimulating sci-fi epic directed by Christopher Nolan, is a cinematic experience that has left audiences worldwide in awe. This beautifully crafted film tells the story of Cooper (Matthew McConaughey), a
            former NASA pilot who leads a mission to explore the possibilities of wormhole travel and find a new habitable planet for humanity.

            The plot follows Cooper's journey as he leaves behind his daughter Murph (Jessica Chastain) and embarks on a perilous quest through space and time. Along with a team of scientists and engineers, including Dr. Brand (Anne Hathaway), Cooper must navigate the
            challenges of wormhole travel, gravitational forces, and the mysterious effects of time dilation.

            One of the standout aspects of Interstellar is its breathtaking visuals. The film's use of practical effects and CGI creates stunning depictions of space, black holes, and wormholes that are both awe-inspiring and terrifying. From the sweeping vistas of space to
            the intimate moments shared between Cooper and Murph, every frame is a feast for the eyes.

            Matthew McConaughey delivers a performance that is both nuanced and captivating, bringing depth and complexity to his character. His chemistry with Jessica Chastain is undeniable, and their bond as father and daughter becomes a highlight of the film. The
            supporting cast is equally impressive, with standout performances from Anne Hathaway and Michael Caine.

            The film's themes of love, sacrifice, and the power of human ingenuity are beautifully woven throughout the narrative, making it a cinematic experience that is both intellectually stimulating and emotionally resonant. Nolan's direction is masterful, with a keen
            eye for detail that brings the world of Interstellar to life in a way that feels both authentic and fantastical.

            However, some critics have argued that Interstellar relies too heavily on complex scientific concepts, making it difficult for non-experts to follow the plot. Others have pointed out the film's lack of emotional resonance with certain characters, particularly
            Cooper's team members.

            While these criticisms are valid, I believe that Interstellar remains a timeless classic that continues to delight audiences worldwide. Its breathtaking visuals, captivating performances, and intellectually stimulating themes make it a cinematic experience that
            is both entertaining and enriching.

            In particular, the film's use of music is noteworthy. The soundtrack features a beautifully crafted score by Hans Zimmer, with songs that are both haunting and uplifting. From "The Wormhole" to "Stay", every song perfectly complements the narrative, adding to
            the overall sense of wonder and magic.

            Ultimately, Interstellar is a film that will leave you feeling inspired, moved, and maybe even a little bit changed. Its stunning visuals, captivating performances, and intellectually stimulating themes make it a cinematic experience that is not to be missed.
            MARKDOWN,
            'Blue valentine' => <<<'MARKDOWN'
            Blue Valentine, the poignant and introspective romantic drama directed by Dennis Villeneuve, is a cinematic experience that has left audiences worldwide in tears. This beautifully crafted film tells the story of Dean (Ryan Gosling), a former high school
            football star who becomes disenchanted with his marriage to Cindy (Michelle Williams).

            The plot follows their tumultuous relationship over the course of several years, from their initial romance and wedding day to their eventual divorce. Through non-linear storytelling and stunning cinematography, Villeneuve captures the ups and downs of their
            relationship, from the euphoric highs of new love to the devastating lows of heartbreak.

            One of the standout aspects of Blue Valentine is its performances. Ryan Gosling and Michelle Williams deliver mesmerizing portrayals of a couple on the brink of collapse, with chemistry that crackles even in the most intense scenes. Their acting is nuanced and
            raw, conveying the complexity and depth of their emotions through subtle facial expressions and body language.

            Villeneuve's direction is masterful, using long takes and close-ups to immerse the viewer in the characters' inner worlds. The film's use of color and lighting is also noteworthy, with a muted palette that perfectly captures the melancholy and desperation of the
            story.

            The screenplay by Scott Neustadter and Michael H. Weber is excellent, conveying the intricate details of the relationship through clever dialogue and symbolism. From the iconic diner scene to the heart-wrenching finale, every moment is expertly crafted to evoke
            a strong emotional response from the audience.

            However, some critics have argued that Blue Valentine is overly bleak, with a narrative that can feel overly pessimistic about relationships. Others have pointed out the film's lack of traditional dramatic structure, which may make it difficult for viewers who
            prefer more conventional storytelling.

            While these criticisms are valid, I believe that Blue Valentine remains a timeless classic that continues to captivate audiences worldwide. Its stunning performances, masterful direction, and poignant screenplay make it a cinematic experience that is both
            emotionally resonant and intellectually stimulating.

            In particular, the film's use of sound design is noteworthy. The soundtrack features a haunting score by Jonny Greenwood, with songs that are both beautiful and haunting. From "All Around You" to "Aurora", every song perfectly complements the narrative, adding
            to the overall sense of mood and atmosphere.

            Ultimately, Blue Valentine is a film that will leave you feeling moved, changed, and maybe even a little bit sad. Its stunning performances, masterful direction, and poignant screenplay make it a cinematic experience that is not to be missed.
            MARKDOWN,
            'Sicario' => <<<'MARKDOWN'
            Sicario, the gritty and intense thriller directed by Denis Villeneuve, is a cinematic experience that will leave you on the edge of your seat. This beautifully crafted film tells the story of Kate Macer (Emily Blunt), a determined DEA agent who becomes
            embroiled in a complex web of corruption and violence along the US-Mexico border.

            The plot follows Kate's investigation into the rise of a powerful cartel, led by the enigmatic and ruthless Alejandro Gillick (Benicio del Toro). As she delves deeper into the case, Kate finds herself torn between her duty as an agent and her growing unease with
            the moral implications of the mission.

            One of the standout aspects of Sicario is its tense atmosphere, masterfully crafted by Villeneuve's direction. The film's use of lighting, sound design, and cinematography creates a sense of claustrophobia and urgency, drawing the viewer into Kate's world of
            danger and deception.

            Emily Blunt delivers a performance that is both nuanced and captivating, conveying the complexity and depth of Kate's character through her subtle expressions and body language. Benicio del Toro is equally impressive, bringing a level of intensity and
            unpredictability to his portrayal of Alejandro.

            The supporting cast is also noteworthy, with standout performances from Jon Bernthal as Matt Graver, the CIA operative who becomes Kate's unlikely ally, and David Strathairn as James Silva, the DEA chief who seems more interested in politics than justice.

            Villeneuve's screenplay is excellent, conveying the intricate details of the case through clever dialogue and symbolism. The film's themes of corruption, power, and morality are timely and thought-provoking, making it a cinematic experience that is both
            intellectually stimulating and emotionally resonant.

            However, some critics have argued that Sicario can feel overly violent and intense, with a narrative that prioritizes action over character development. Others have pointed out the film's lack of traditional resolution, which may leave viewers feeling
            unsatisfied or uncertain about the outcome.

            While these criticisms are valid, I believe that Sicario remains a masterful thriller that continues to captivate audiences worldwide. Its tense atmosphere, nuanced performances, and thought-provoking themes make it a cinematic experience that is both thrilling
            and intellectually stimulating.

            In particular, the film's use of music is noteworthy. The soundtrack features a haunting score by Jóhann Jóhannsson, with songs that are both atmospheric and unsettling. From "Escuela" to "Silva", every song perfectly complements the narrative, adding to the
            overall sense of tension and unease.

            Ultimately, Sicario is a film that will leave you feeling shaken, disturbed, and maybe even a little bit uncomfortable. Its tense atmosphere, nuanced performances, and thought-provoking themes make it a cinematic experience that is not to be missed.
            MARKDOWN,
            'Oppenheimer' => <<<'MARKDOWN'
            Oppenheimer, Christopher Nolan's biographical drama about the life of J. Robert Oppenheimer, the American physicist who led the Manhattan Project, is a stunning film that explores the depths of human creativity and internal conflict.

            The story follows Oppenheimer (Cillian Murphy) from his early days as a brilliant scientist at Princeton to his involvement in the Manhattan Project, charged with developing the atomic bomb. The film also tackles his implication in the atomic program and its
            consequences on his personal life.

            Christopher Nolan is a master of visual realism, and "Oppenheimer" doesn't disappoint. The sets and costumes are meticulously detailed and authentic, plunging the viewer into the scientific and political world of World War II. Hoyte van Hoytema's cinematography
            is particularly noteworthy, capturing with precision the textures and colors of the scenes.

            Cillian Murphy brings Oppenheimer to life with an intensity and complexity that makes him a fascinating character. He conveys the tension and ambiguity of the character, who is both drawn to scientific power but also disgusted by the moral consequences of his
            discoveries.

            Nolan's direction is, as usual, impeccable. He uses time and space to create a sense of depth and complexity, making "Oppenheimer" a film that rewards close attention and patience.

            However, some critics have noted that the film can be confusing in its portrayal of secondary characters. The character of Katherine Puening (Emily Blunt), for example, is underdeveloped and seems to have been neglected in the scriptwriting process.

            Overall, "Oppenheimer" is a film that rewards perseverance and dedication from its director. It's a complex and moving story that forces us to think about the consequences of human ingenuity. It's a crucial cinematic moment, a film that reminds us of the
            importance of moral consciousness in tumultuous times.

            In particular, I want to highlight the emerging talent of Lily Gladstone as Kate Courmand. She brings intensity and empathy to the character of Oppenheimer's wife, giving us a glimpse into his personal life and a more nuanced understanding of the man behind the
            myth.
            MARKDOWN,
            'Inception' => <<<'MARKDOWN'
            Inception" is a thought-provoking sci-fi action film that delves into the complexities of the human mind. Director Christopher Nolan weaves a intricate narrative that explores the concept of shared dreaming and the blurring of reality. The plot follows Cobb
            (Leonardo DiCaprio), a skilled thief who specializes in entering people's dreams and stealing their secrets.

            As Cobb is tasked with planting an idea in someone's mind instead of stealing one, he must navigate multiple levels of dreams within dreams. This layered structure adds to the film's complexity and requires close attention from the viewer. Nolan's use of
            non-linear storytelling keeps the audience engaged and curious about what's real and what's just a dream.

            The cast delivers impressive performances, with DiCaprio bringing his signature intensity to the role of Cobb. Marion Cotillard shines as Mal, Cobb's wife who may or may not be a product of one of his own dreams. Tom Hardy and Joseph Gordon-Levitt round out the
            cast, playing characters who are both menacing and sympathetic.

            The visuals in "Inception" are stunning, with Wally Pfister's cinematography capturing the vibrant colors and surreal landscapes of the dream world. The action sequences are equally impressive, with Nolan expertly choreographing the fight scenes and stunts to
            keep the audience on the edge of their seats.

            One of the film's greatest strengths is its thematic depth. Nolan explores complex ideas about reality, free will, and the nature of identity, raising questions that linger long after the credits roll. The film's twist ending is both surprising and
            thought-provoking, leaving the viewer to ponder the implications of Cobb's actions.

            If there's one criticism to be made, it's that some viewers may find the pacing a bit uneven. The second half of the film can feel a bit rushed, with certain plot points glossed over in favor of the action-packed finale. However, this is a minor quibble in what
            is otherwise a gripping and engaging cinematic experience.

            Overall, "Inception" is a must-see for fans of sci-fi and action films. Its intricate plot, impressive visuals, and thought-provoking themes make it a standout in Nolan's oeuvre. Whether you're a die-hard fan of the director or just looking for a compelling
            watch, "Inception" is sure to deliver.
            MARKDOWN,
            'The Revenant' => <<<'MARKDOWN'
            Martin Scorsese's 2015 epic drama "The Revenant" is a masterclass in cinematography and performances, but its exploration of colonialism, violence, and survival also raises uncomfortable
            questions about the ethics of storytelling.
            
            The film tells the true story of Hugh Glass (played by Leonardo DiCaprio), a fur trapper mauled by a bear and left for dead in the early 19th-century American wilderness. As Glass navigates
            the treacherous terrain, he seeks revenge against those who betrayed him, including his former mentor John Fitzgerald (Tom Hardy) and the expedition's leader Andrew Henry (Will Poulter).
            
            DiCaprio's performance is, without a doubt, one of the most remarkable aspects of the film. His portrayal of Glass is both physically and emotionally demanding, capturing the grueling
            struggles of survival in the harsh environment. His nuanced performances brings depth to the character, making it easy to sympathize with his plight.
            
            However, Scorsese's direction also raises questions about the representation of indigenous peoples in the film. The Native American characters are largely relegated to secondary roles and are
            often depicted as one-dimensional, violent savages. This perpetuates a damaging stereotype that has been used for centuries to justify colonialism and oppression.
            
            Furthermore, Fitzgerald's character can be seen as a symbol of the exploitation of the natural world by European colonizers. His betrayal of Glass is motivated by greed and a desire to claim
            the land for himself, highlighting the destructive impact of imperialism on indigenous cultures.
            
            In addition, the film's portrayal of violence is both visceral and gratuitous. The graphic depiction of bear attacks and human brutality serves as a reminder that the American wilderness can
            be a cruel and unforgiving environment. However, this focus on violence also detracts from the emotional core of the story, reducing Glass's journey to mere survival.
            
            Ultimately, "The Revenant" is a film that presents complex themes and challenging moral dilemmas. While it is an visually stunning and emotionally resonant experience, it is also essential to
            acknowledge its limitations and flaws. By examining these issues, we can gain a deeper understanding of the cultural and historical context in which the film was made.
            MARKDOWN,
            'Mad Max: Fury Road' => <<<'MARKDOWN'
            George Miller's 2015 post-apocalyptic action film "Mad Max: Fury Road" is a visceral and visually stunning ride, but its critique of patriarchal society and consumerism also raises important
            questions about the themes and tone of the movie.
            
            The film takes place in a dystopian future where resources are scarce and violence is rampant. Imperator Furiosa (Charlize Theron), a tough-as-nails survivor, joins forces with Max Rockatansky
            (Tom Hardy) to take down the tyrannical leader Immortan Joe (Hugh Keays-Byrne). As they navigate the treacherous landscape, they engage in high-octane action sequences and witty banter.
            
            One of the most striking aspects of "Fury Road" is its portrayal of Furiosa as a strong, independent woman who defies traditional feminine norms. Theron's performance brings depth and nuance
            to the character, making her more than just a mere action heroine. Her complexity and vulnerability make it easy to root for her, and her relationship with Max is fraught with tension and
            emotion.
            
            However, some critics have argued that Furiosa's character can be seen as reinforcing traditional feminine stereotypes - she is sexy, tough, and resourceful, but also emotionally fragile and
            dependent on men. This criticism highlights the tension between feminism and postfeminism in the film, where a strong female protagonist can both empower women and perpetuate patriarchal
            attitudes.
            
            Furthermore, the film's portrayal of a society that values beauty, luxury, and violence above all else is unsettlingly familiar. The wasteland landscape, filled with junkyards and ruined
            cities, serves as a commentary on our own consumerist culture. The characters' obsession with cars and high-tech gadgets is seen as a distraction from the world around them, highlighting the
            dangers of escapism.
            
            In addition, the film's depiction of masculinity is also worth examining. Max, Furiosa, and other male characters are often reduced to simplistic, macho archetypes - they are loud, aggressive,
            and violent. This reinforces the idea that masculinity is tied to aggression and dominance, rather than vulnerability and empathy.
            
            Ultimately, "Mad Max: Fury Road" is a film that uses action and spectacle to critique the darker aspects of human nature. While it has its flaws, its exploration of patriarchal society and
            consumerism is thought-provoking and timely. By examining these themes, we can gain a deeper understanding of the cultural commentary at play.
            MARKDOWN,
            'Moonlight' => <<<'MARKDOWN'
            Barry Jenkins' 2016 drama "Moonlight" is a poignant and powerful exploration of identity, masculinity, and the struggles of growing up as a black man in America.
            
            The film follows the life of Chiron (played by Trevante Rhodes), a young black boy from Miami who navigates the challenges of adolescence with courage and vulnerability. As he grows older,
            Chiron grapples with his own identity, struggling to reconcile his desire for connection and intimacy with the harsh realities of his environment.
            
            One of the most striking aspects of "Moonlight" is its portrayal of masculinity. The film challenges traditional notions of manhood, instead emphasizing the fragility and vulnerability of male
            emotions. The characters are multidimensional and complex, refusing to fit into simplistic or binary categories.
            
            Tarell Alvin McCraney's script, based on his own experiences growing up in Miami, brings a level of authenticity and nuance to the story. His writing is lyrical and poetic, capturing the
            beauty and brutality of life in the inner city. The film's use of music is also noteworthy, with a haunting soundtrack that perfectly complements the mood and tone.
            
            The performances are equally impressive, particularly Rhodes' portrayal of Chiron as an adult. His ability to convey vulnerability and strength simultaneously makes for a deeply moving and
            relatable character.
            
            However, some critics have argued that the film's portrayal of poverty and violence can be overwhelming and gratuitous. The scenes of street fights, police brutality, and domestic abuse are
            graphic and intense, which may not be suitable for all audiences.
            
            Furthermore, the film's focus on the experiences of a single black male character raises questions about representation and diversity. While "Moonlight" is widely regarded as one of the most
            important films of the past decade, its lack of female characters and perspectives has been criticized by some.
            
            Ultimately, "Moonlight" is a powerful exploration of identity and human connection that lingers long after the credits roll. Its portrayal of masculinity is particularly noteworthy,
            challenging traditional notions and offering a more nuanced and compassionate understanding of what it means to be male.
            MARKDOWN,
            'The social network' => <<<'MARKDOWN'
            David Fincher's 2010 biographical drama "The Social Network" is a thought-provoking exploration of the consequences of ambition, power, and fame.
            
            The film tells the story of Mark Zuckerberg (played by Jesse Eisenberg), a Harvard student who creates a social networking site that quickly gains popularity. As Facebook becomes a global
            phenomenon, Zuckerberg's relationship with his friends and colleagues deteriorates, leading to a series of lawsuits and scandals.
            
            One of the most striking aspects of "The Social Network" is its portrayal of Mark Zuckerberg as a complex and multifaceted character. Eisenberg brings depth and nuance to the role, capturing
            both the brilliance and the arrogance that defines Zuckerberg's personality.
            
            The film also raises questions about the nature of success and fame. The characters' obsession with Facebook and its impact on society serves as a commentary on our own culture of instant
            gratification and social media addiction.
            
            The performances are outstanding, particularly Eisenberg, Justin Timberlake (who plays Sean Parker), and Andrew Garfield (who plays Eduardo Saverin). The cast delivers complex and layered
            performances that add depth to the story.
            
            However, some critics have argued that the film's portrayal of Facebook and its founders is overly simplistic. The characters' motivations are often reduced to caricatures, with Zuckerberg
            being depicted as a genius with a slightly awkward personality.
            
            Furthermore, the film's focus on the founding of Facebook overlooks the broader social and cultural context in which it emerged. The film touches on issues of identity, community, and power,
            but these themes are not fully explored.

            Ultimately, "The Social Network" is a thought-provoking exploration of the consequences of ambition and fame. Its portrayal of Mark Zuckerberg as a complex and multifaceted character raises
            important questions about the nature of success and our society's obsession with social media.
            MARKDOWN,
            'Chocolat' => <<<'MARKDOWN'
            "Chocolat" is a film that explores the themes of freedom, love, and personal discovery. The movie follows the story of a woman named Vianne Rocher (played by Juliette Binoche) who opens a
            chocolate shop in a small village in France.
            
            The film is a treat for the senses, with memorable scenes of shooting, including the sequence in the whichier where Vianne serves chocolates to her customers. The camera works with light and
            colors as if they were tools of sculpture.
            
            The performances are excellent, Juliette Binoche is incredibly alive in this role, making us feel like we're experiencing the journey of a woman who's trying to liberate her village from the
            rules and traditions of her childhood. The supporting actors, such as Johnny Depp (Count von Ségur), deliver outstanding performances.
            
            The settings are elegant, with a mix of rusticness and sophistication that perfectly matches the film's atmosphere. The music is magnificent, the score is very evocative and corresponds
            perfectly to the scenes.
            
            The story is simple but effective, following the progress of a woman who's trying to liberate her village from the rules and traditions of her childhood. It's funny and touching at the same
            time.
            
            "Chocolat" is a film that will leave you dreaming of chocolate, freedom, and love. It's an experiential vision that will charm you.
            MARKDOWN,
            'Ratatouille' => <<<'MARKDOWN'
            In this film, we follow Remy's adventures, a rat with exceptional culinary skills. Voilà who makes you dream! The movie is an ode to the magic of French cuisine, where flavors and aromas come
            together to create unforgettable dishes.
            
            The characters are all well-represented, with Remy as the head chef. He also has Linguini, the young pastry chef who becomes his working partner. Both are incredibly lively and make you laugh
            every time.
            
            But what makes "Ratatouille" really special is the cuisine! Oh, the dishes! The sauces! Fresh ingredients! It's like being in a great Parisian kitchen. And the realization is impeccable:
            animals are beautifully designed and animated.
            
            The music is also very evocative and perfectly corresponds to the scenes. The song "Tutto il Ciel è Natale" is a true gem!
            
            In short, "Ratatouille" is a film that will make you dream of French cuisine, freedom, friendship, and magic. It's an experiential vision that will leave you under its spell.
            MARKDOWN,
        ];
    }
}
