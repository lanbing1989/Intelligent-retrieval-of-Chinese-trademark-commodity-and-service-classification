import re
import mysql.connector

# 配置
TXT_PATH = '45.txt'  # 你的txt文件名
MYSQL_CONFIG = {
    'host': 'localhost',
    'user': 'sbfl_wsx_tax',
    'password': 'AZkEN16rfQANBwB7',
    'database': 'sbfl_wsx_tax',
    'charset': 'utf8mb4'
}

def parse_txt(txt_path):
    with open(txt_path, 'r', encoding='utf-8') as f:
        lines = f.readlines()

    categories = []
    subclasses = []
    current_category = None
    current_category_desc = []
    current_subclass = None
    current_subclass_content = []

    for line in lines:
        l = line.strip()
        # 跳过空行
        if not l:
            continue

        # 识别大类（如“第一类”）
        m_cat = re.match(r'^第([一二三四五六七八九十]{1,3})类$', l)
        if m_cat:
            # 保存上一个大类
            if current_category:
                if current_category_desc:
                    current_category['description'] = '\n'.join(current_category_desc).strip()
                categories.append(current_category)
            zh_num = m_cat.group(1)
            zh_map = {'一': '01', '二': '02', '三': '03', '四': '04', '五': '05', '六': '06', '七': '07', '八': '08', '九': '09', '十': '10'}
            code = zh_map.get(zh_num, zh_num.zfill(2))
            current_category = {'code': code, 'name': l, 'description': ''}
            current_category_desc = []
            current_subclass = None
            continue

        # 识别小类（如“0101工业气体，单质”）
        m_sub = re.match(r'^(\d{4})([^\d]+)', l)
        if m_sub and current_category:
            # 保存上一个小类
            if current_subclass:
                if current_subclass_content:
                    current_subclass['content'] = '\n'.join(current_subclass_content).strip()
                subclasses.append(current_subclass)
            code = m_sub.group(1)
            title = m_sub.group(2).strip()
            current_subclass = {
                'category_code': current_category['code'],
                'code': code,
                'title': title,
                'content': ''
            }
            current_subclass_content = []
            continue

        # 分类注释和描述（大类描述在遇到下一个小类前出现）
        if current_subclass:
            current_subclass_content.append(l)
        elif current_category:
            current_category_desc.append(l)

    # 收尾
    if current_subclass:
        if current_subclass_content:
            current_subclass['content'] = '\n'.join(current_subclass_content).strip()
        subclasses.append(current_subclass)
    if current_category:
        if current_category_desc:
            current_category['description'] = '\n'.join(current_category_desc).strip()
        categories.append(current_category)

    return categories, subclasses

def save_to_mysql(categories, subclasses):
    conn = mysql.connector.connect(**MYSQL_CONFIG)
    cur = conn.cursor()
    # 插入category表
    cat_code2id = {}
    for cat in categories:
        cur.execute("INSERT INTO category (code, name, description) VALUES (%s, %s, %s)", 
            (cat['code'], cat['name'], cat.get('description', '')))
        cat_id = cur.lastrowid
        cat_code2id[cat['code']] = cat_id
    conn.commit()

    # 插入subclass表
    for sub in subclasses:
        cat_id = cat_code2id.get(sub['category_code'])
        cur.execute("INSERT INTO subclass (category_id, code, title, content, img_paths) VALUES (%s, %s, %s, %s, %s)",
            (cat_id, sub['code'], sub['title'], sub['content'], ''))
    conn.commit()
    cur.close()
    conn.close()

if __name__ == "__main__":
    categories, subclasses = parse_txt(TXT_PATH)
    print(f"检测到大类 {len(categories)} 个, 小类 {len(subclasses)} 个")
    save_to_mysql(categories, subclasses)
    print("导入完成！")