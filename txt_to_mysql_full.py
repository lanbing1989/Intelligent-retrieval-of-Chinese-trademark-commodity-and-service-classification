#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
商标分类数据导入工具 - 优化版本
支持从文本文件导入到MySQL数据库
"""

import re
import sys
import argparse
import mysql.connector
from mysql.connector import Error
from typing import List, Dict, Tuple, Optional
import logging

# 配置日志
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s',
    datefmt='%Y-%m-%d %H:%M:%S'
)
logger = logging.getLogger(__name__)


class DatabaseConfig:
    """数据库配置类"""
    def __init__(self, host: str, user: str, password: str, database: str, charset: str = 'utf8mb4'):
        self.host = host
        self.user = user
        self.password = password
        self.database = database
        self.charset = charset

    def to_dict(self) -> Dict:
        return {
            'host': self.host,
            'user': self.user,
            'password': self.password,
            'database': self.database,
            'charset': self.charset
        }


class TrademarkImporter:
    """商标分类数据导入器"""
    
    # 中文数字映射
    ZH_NUM_MAP = {
        '一': '1', '二': '2', '三': '3', '四': '4', '五': '5',
        '六': '6', '七': '7', '八': '8', '九': '9', '十': '10',
        '十一': '11', '十二': '12', '十三': '13', '十四': '14', '十五': '15',
        '十六': '16', '十七': '17', '十八': '18', '十九': '19', '二十': '20',
        '二十一': '21', '二十二': '22', '二十三': '23', '二十四': '24', '二十五': '25',
        '二十六': '26', '二十七': '27', '二十八': '28', '二十九': '29', '三十': '30',
        '三十一': '31', '三十二': '32', '三十三': '33', '三十四': '34', '三十五': '35',
        '三十六': '36', '三十七': '37', '三十八': '38', '三十九': '39', '四十': '40',
        '四十一': '41', '四十二': '42', '四十三': '43', '四十四': '44', '四十五': '45'
    }
    
    def __init__(self, db_config: DatabaseConfig, txt_path: str):
        self.db_config = db_config
        self.txt_path = txt_path
        self.conn = None
        self.cursor = None
    
    def zh_to_num(self, zh: str) -> str:
        """中文数字转换为阿拉伯数字"""
        if zh in self.ZH_NUM_MAP:
            return self.ZH_NUM_MAP[zh]
        
        # 处理组合数字
        if len(zh) == 1:
            return self.ZH_NUM_MAP.get(zh, zh)
        if zh.startswith('十'):
            return '1' + self.ZH_NUM_MAP.get(zh[1:], zh[1:])
        if zh.endswith('十'):
            return self.ZH_NUM_MAP.get(zh[:-1], zh[:-1]) + '0'
        if '十' in zh:
            parts = zh.split('十')
            left = self.ZH_NUM_MAP.get(parts[0], parts[0]) if parts[0] else '1'
            right = self.ZH_NUM_MAP.get(parts[1], parts[1]) if len(parts) > 1 and parts[1] else ''
            return left + right
        return zh
    
    @staticmethod
    def full_strip(s: str) -> str:
        """清理字符串"""
        return s.strip().replace('\u3000', '').replace('\xa0', '').replace('\ufeff', '')
    
    def connect_db(self) -> bool:
        """连接数据库"""
        try:
            self.conn = mysql.connector.connect(**self.db_config.to_dict())
            self.cursor = self.conn.cursor()
            logger.info(f"成功连接到数据库: {self.db_config.database}")
            return True
        except Error as e:
            logger.error(f"数据库连接失败: {e}")
            return False
    
    def close_db(self):
        """关闭数据库连接"""
        if self.cursor:
            self.cursor.close()
        if self.conn:
            self.conn.close()
            logger.info("数据库连接已关闭")
    
    def clear_existing_data(self):
        """清空现有数据"""
        try:
            self.cursor.execute("SET FOREIGN_KEY_CHECKS = 0")
            self.cursor.execute("TRUNCATE TABLE subclass")
            self.cursor.execute("TRUNCATE TABLE category")
            self.cursor.execute("SET FOREIGN_KEY_CHECKS = 1")
            self.conn.commit()
            logger.info("已清空现有数据")
        except Error as e:
            logger.error(f"清空数据失败: {e}")
            raise
    
    def parse_txt(self) -> Tuple[List[Dict], List[Dict]]:
        """解析文本文件"""
        logger.info(f"开始解析文件: {self.txt_path}")
        
        try:
            with open(self.txt_path, 'r', encoding='utf-8') as f:
                lines = f.readlines()
        except FileNotFoundError:
            logger.error(f"文件不存在: {self.txt_path}")
            raise
        except Exception as e:
            logger.error(f"读取文件失败: {e}")
            raise
        
        categories = []
        subclasses = []
        current_category = None
        current_category_desc = []
        current_subclass = None
        current_subclass_content = []
        waiting_subclass_title = None
        
        for idx, line in enumerate(lines):
            l = self.full_strip(line)
            if not l:
                continue
            
            # 匹配大类标题
            m_cat = re.match(r'^第([一二三四五六七八九十]{1,3})类$', l)
            if m_cat:
                # 保存之前的小类
                if current_subclass:
                    if current_subclass_content:
                        current_subclass['content'] = '\n'.join(current_subclass_content).strip()
                    subclasses.append(current_subclass)
                    current_subclass = None
                    current_subclass_content = []
                
                # 保存之前的大类
                if current_category:
                    if current_category_desc:
                        current_category['description'] = '\n'.join(current_category_desc).strip()
                    categories.append(current_category)
                
                code = self.zh_to_num(m_cat.group(1)).zfill(2)
                current_category = {'code': code, 'name': l, 'description': ''}
                current_category_desc = []
                waiting_subclass_title = None
                continue
            
            # 匹配小类代码
            m_sub = re.match(r'^(\d{4})([^\d]*)$', l)
            if m_sub and current_category:
                # 保存之前的小类
                if current_subclass:
                    if current_subclass_content:
                        current_subclass['content'] = '\n'.join(current_subclass_content).strip()
                    subclasses.append(current_subclass)
                
                code = m_sub.group(1)
                title = m_sub.group(2).strip()
                
                if title:
                    current_subclass = {
                        'category_code': current_category['code'],
                        'code': code,
                        'title': title,
                        'content': ''
                    }
                    current_subclass_content = []
                    waiting_subclass_title = None
                else:
                    waiting_subclass_title = code
                    current_subclass = None
                    current_subclass_content = []
                continue
            
            # 处理等待小类标题的情况
            if waiting_subclass_title and l:
                current_subclass = {
                    'category_code': current_category['code'],
                    'code': waiting_subclass_title,
                    'title': l,
                    'content': ''
                }
                current_subclass_content = []
                waiting_subclass_title = None
                continue
            
            # 累积内容
            if current_subclass:
                current_subclass_content.append(l)
            elif current_category:
                current_category_desc.append(l)
        
        # 处理最后的数据
        if current_subclass:
            if current_subclass_content:
                current_subclass['content'] = '\n'.join(current_subclass_content).strip()
            subclasses.append(current_subclass)
        
        if current_category:
            if current_category_desc:
                current_category['description'] = '\n'.join(current_category_desc).strip()
            categories.append(current_category)
        
        logger.info(f"解析完成: {len(categories)} 个大类, {len(subclasses)} 个小类")
        return categories, subclasses
    
    def validate_data(self, categories: List[Dict], subclasses: List[Dict]) -> bool:
        """验证数据完整性"""
        if not categories:
            logger.error("没有找到大类数据")
            return False
        
        if not subclasses:
            logger.error("没有找到小类数据")
            return False
        
        # 检查大类代码是否连续
        cat_codes = sorted([int(c['code']) for c in categories])
        expected_codes = list(range(1, 46))  # 45类
        
        missing = set(expected_codes) - set(cat_codes)
        if missing:
            logger.warning(f"缺少大类: {sorted(missing)}")
        
        # 检查小类是否有对应的大类
        cat_code_set = set(c['code'] for c in categories)
        orphan_subclasses = [s for s in subclasses if s['category_code'] not in cat_code_set]
        if orphan_subclasses:
            logger.warning(f"有 {len(orphan_subclasses)} 个小类没有对应的大类")
        
        logger.info("数据验证通过")
        return True
    
    def save_to_mysql(self, categories: List[Dict], subclasses: List[Dict]):
        """保存到数据库"""
        if not self.conn:
            raise RuntimeError("数据库未连接")
        
        logger.info("开始保存到数据库...")
        
        try:
            # 保存大类
            cat_code2id = {}
            cat_sql = "INSERT INTO category (code, name, description) VALUES (%s, %s, %s)"
            
            for cat in categories:
                self.cursor.execute(cat_sql, (
                    cat['code'],
                    cat['name'],
                    cat.get('description', '')
                ))
                cat_id = self.cursor.lastrowid
                cat_code2id[cat['code']] = cat_id
            
            self.conn.commit()
            logger.info(f"已保存 {len(categories)} 个大类")
            
            # 保存小类
            sub_sql = "INSERT INTO subclass (category_id, code, title, content, img_paths) VALUES (%s, %s, %s, %s, %s)"
            sub_values = []
            
            for sub in subclasses:
                cat_id = cat_code2id.get(sub['category_code'])
                if cat_id:
                    sub_values.append((
                        cat_id,
                        sub['code'],
                        sub['title'],
                        sub.get('content', ''),
                        ''
                    ))
            
            if sub_values:
                self.cursor.executemany(sub_sql, sub_values)
                self.conn.commit()
                logger.info(f"已保存 {len(sub_values)} 个小类")
            
        except Error as e:
            self.conn.rollback()
            logger.error(f"保存数据失败: {e}")
            raise
    
    def create_indexes(self):
        """创建索引以优化查询性能"""
        logger.info("创建索引...")
        
        indexes = [
            ("idx_subclass_category", "CREATE INDEX idx_subclass_category ON subclass(category_id)"),
            ("idx_subclass_code", "CREATE INDEX idx_subclass_code ON subclass(code)"),
            ("idx_subclass_title", "CREATE FULLTEXT INDEX idx_subclass_title ON subclass(title)"),
            ("idx_subclass_content", "CREATE FULLTEXT INDEX idx_subclass_content ON subclass(content)"),
            ("idx_category_code", "CREATE INDEX idx_category_code ON category(code)")
        ]
        
        for index_name, sql in indexes:
            try:
                self.cursor.execute(sql)
                logger.info(f"创建索引成功: {index_name}")
            except Error as e:
                if 'Duplicate' in str(e) or 'already exists' in str(e):
                    logger.info(f"索引已存在: {index_name}")
                else:
                    logger.warning(f"创建索引失败 {index_name}: {e}")
        
        self.conn.commit()
    
    def run(self, clear_data: bool = False, create_indexes: bool = True):
        """运行导入流程"""
        try:
            # 连接数据库
            if not self.connect_db():
                return False
            
            # 清空数据
            if clear_data:
                self.clear_existing_data()
            
            # 解析数据
            categories, subclasses = self.parse_txt()
            
            # 验证数据
            if not self.validate_data(categories, subclasses):
                return False
            
            # 保存数据
            self.save_to_mysql(categories, subclasses)
            
            # 创建索引
            if create_indexes:
                self.create_indexes()
            
            logger.info("导入完成!")
            return True
            
        except Exception as e:
            logger.error(f"导入过程出错: {e}")
            return False
        finally:
            self.close_db()


def main():
    parser = argparse.ArgumentParser(description='商标分类数据导入工具')
    parser.add_argument('file', nargs='?', default='45.txt', help='输入文本文件路径 (默认: 45.txt)')
    parser.add_argument('--host', default='localhost', help='数据库主机 (默认: localhost)')
    parser.add_argument('--user', required=True, help='数据库用户名')
    parser.add_argument('--password', required=True, help='数据库密码')
    parser.add_argument('--database', required=True, help='数据库名称')
    parser.add_argument('--clear', action='store_true', help='清空现有数据')
    parser.add_argument('--no-index', action='store_true', help='不创建索引')
    
    args = parser.parse_args()
    
    # 创建配置
    db_config = DatabaseConfig(
        host=args.host,
        user=args.user,
        password=args.password,
        database=args.database
    )
    
    # 运行导入
    importer = TrademarkImporter(db_config, args.file)
    success = importer.run(
        clear_data=args.clear,
        create_indexes=not args.no_index
    )
    
    sys.exit(0 if success else 1)


if __name__ == "__main__":
    # 默认配置（用于直接运行）
    import os
    
    if len(sys.argv) == 1:
        # 使用默认配置
        db_config = DatabaseConfig(
            host=os.getenv('DB_HOST', 'localhost'),
            user=os.getenv('DB_USER', 'r_zhuli_pro'),
            password=os.getenv('DB_PASS', 'Sfptryfe822ytHh5'),
            database=os.getenv('DB_NAME', 'r_zhuli_pro')
        )
        
        importer = TrademarkImporter(db_config, '45.txt')
        success = importer.run(clear_data=True, create_indexes=True)
        
        if success:
            print("\n导入成功!")
        else:
            print("\n导入失败!")
            sys.exit(1)
    else:
        main()
